<?php

use App\Filament\Pages\Reports;
use App\Models\DisbursementVoucher;
use App\Models\Fund;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->office = Office::factory()->create(['name' => 'College of Engineering']);
    $this->otherOffice = Office::factory()->create();
    $this->officeRequester = User::factory()->create(['office_id' => $this->office->id]);
    $this->otherRequester = User::factory()->create(['office_id' => $this->otherOffice->id]);

    $this->vouchers = collect(['draft', 'submitted', 'returned', 'for_payment', 'completed'])
        ->mapWithKeys(fn (string $status): array => [
            $status => DisbursementVoucher::factory()->create(['status' => $status, 'created_by' => $this->officeRequester->id]),
        ]);
    $this->otherOfficeVoucher = DisbursementVoucher::factory()->create(['status' => 'completed', 'created_by' => $this->otherRequester->id]);
});

function userWithRole(string $role, array $attributes = []): User
{
    Role::findOrCreate($role);
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

it('is available to every workflow role and the admin', function (string $role) {
    $this->actingAs(userWithRole($role))
        ->get(Reports::getUrl())
        ->assertOk();
})->with(['super_admin', 'requesting_unit', 'finance_processor', 'finance_supervisor']);

it('is forbidden to a user without a workflow role', function () {
    $this->actingAs(userWithRole('panel_user'))
        ->get(Reports::getUrl())
        ->assertForbidden();
});

it('shows each role only the vouchers that concern it', function (string $role, array $visibleStatuses, bool $seesOtherOffice) {
    $this->actingAs(userWithRole($role, ['office_id' => $this->office->id]));

    $visible = $this->vouchers->only($visibleStatuses)->values();
    $hidden = $this->vouchers->except($visibleStatuses)->values();

    Livewire::test(Reports::class)
        ->assertCanSeeTableRecords($visible)
        ->assertCanNotSeeTableRecords($hidden)
        ->{$seesOtherOffice ? 'assertCanSeeTableRecords' : 'assertCanNotSeeTableRecords'}([$this->otherOfficeVoucher]);
})->with([
    'admin sees everything' => ['super_admin', ['draft', 'submitted', 'returned', 'for_payment', 'completed'], true],
    'requesting unit sees its office' => ['requesting_unit', ['draft', 'submitted', 'returned', 'for_payment', 'completed'], false],
    'processor sees everything past draft' => ['finance_processor', ['submitted', 'returned', 'for_payment', 'completed'], true],
    'supervisor sees forwarded vouchers' => ['finance_supervisor', ['for_payment', 'completed'], true],
]);

it('downloads the filtered vouchers as a spreadsheet', function () {
    $this->freezeSecond();
    $fund = Fund::factory()->create(['code' => 'FC05-STF', 'name' => 'Special Trust Fund']);
    $this->vouchers['completed']->update(['fund_id' => $fund->id, 'amount' => 12345.5, 'dv_no' => 'DV-2026-777']);

    $this->actingAs(userWithRole('super_admin'));

    $component = Livewire::test(Reports::class)
        ->filterTable('status', ['completed'])
        ->callAction(TestAction::make('download')->table())
        ->assertFileDownloaded('dv-report-'.now()->format('Y-m-d-His').'.csv');

    $csv = base64_decode($component->effects['download']['content']);
    $rows = array_map(str_getcsv(...), explode("\n", trim(substr($csv, 3))));

    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($rows[0])->toContain('DV No.', 'Office', 'Fund Code', 'Amount (PHP)', 'Status', 'Date Completed')
        ->and($rows)->toHaveCount(3)
        ->and($csv)->toContain('DV-2026-777', 'FC05-STF', '12345.50', 'College of Engineering', 'Completed')
        ->not->toContain($this->vouchers['draft']->dv_no);
});

it('calculates age, days at the current stage, and the aging bucket', function () {
    $this->freezeSecond();
    $this->actingAs(userWithRole('finance_processor'));
    $stage = ProcessingStage::factory()->create();

    $open = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'submitted_at' => now()->subDays(20)]);
    RoutingHistory::create(['disbursement_voucher_id' => $open->id, 'processing_stage_id' => $stage->id, 'action' => 'forwarded', 'acted_at' => now()->subDays(6)]);
    $completed = DisbursementVoucher::factory()->create(['status' => 'completed', 'submitted_at' => now()->subDays(40), 'completed_at' => now()->subDays(30)]);
    $draft = DisbursementVoucher::factory()->create(['status' => 'draft', 'submitted_at' => null]);

    expect($open->ageInDays())->toBe(20)
        ->and($open->daysAtCurrentStage())->toBe(6)
        ->and($open->agingBucket())->toBe('16-30')
        ->and($completed->ageInDays())->toBe(10)
        ->and($completed->daysAtCurrentStage())->toBeNull()
        ->and($completed->agingBucket())->toBe('8-15')
        ->and($draft->ageInDays())->toBeNull()
        ->and($draft->agingBucket())->toBeNull();
});

it('filters open vouchers by aging bucket', function () {
    $this->freezeSecond();
    $fresh = DisbursementVoucher::factory()->create(['status' => 'submitted', 'submitted_at' => now()->subDays(3)]);
    $boundary = DisbursementVoucher::factory()->create(['status' => 'returned', 'submitted_at' => now()->subDays(15)]);
    $overdue = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'submitted_at' => now()->subDays(45)]);
    $oldButCompleted = DisbursementVoucher::factory()->create(['status' => 'completed', 'submitted_at' => now()->subDays(45), 'completed_at' => now()]);

    $this->actingAs(userWithRole('super_admin'));

    Livewire::test(Reports::class)
        ->filterTable('aging', '8-15')
        ->assertCanSeeTableRecords([$boundary])
        ->assertCanNotSeeTableRecords([$fresh, $overdue, $oldButCompleted])
        ->filterTable('aging', '31+')
        ->assertCanSeeTableRecords([$overdue])
        ->assertCanNotSeeTableRecords([$fresh, $boundary, $oldButCompleted]);
});

it('includes aging columns in the spreadsheet', function () {
    $this->freezeSecond();
    $this->vouchers['submitted']->update(['submitted_at' => now()->subDays(33), 'dv_no' => 'DV-2026-900']);

    $this->actingAs(userWithRole('super_admin'));

    $component = Livewire::test(Reports::class)
        ->filterTable('status', ['submitted'])
        ->callAction(TestAction::make('download')->table());

    $rows = array_map(str_getcsv(...), explode("\n", trim(substr(base64_decode($component->effects['download']['content']), 3))));
    $row = array_combine($rows[0], collect($rows)->first(fn (array $row): bool => $row[0] === 'DV-2026-900'));

    expect($row['Age (Days)'])->toBe('33')
        ->and($row['Aging Bucket'])->toBe('Over 30 days')
        ->and($rows[0])->toContain('Days at Current Stage');
});
