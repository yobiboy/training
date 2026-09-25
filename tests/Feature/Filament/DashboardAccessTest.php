<?php

use App\Filament\Pages\RequestingUnitDashboard;
use App\Filament\Widgets\FinanceProcessorDvOverview;
use App\Filament\Widgets\FinanceSupervisorDvOverview;
use App\Filament\Widgets\RequestingUnitDvOverview;
use App\Filament\Widgets\WelcomeWidget;
use App\Models\DisbursementVoucher;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\User;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('shows each role its own stats widget on the dashboard', function (string $role, string $ownWidget, array $otherWidgets) {
    Role::findOrCreate($role);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSeeLivewire($ownWidget);

    foreach ($otherWidgets as $otherWidget) {
        $response->assertDontSeeLivewire($otherWidget);
    }
})->with([
    'requesting_unit' => ['requesting_unit', RequestingUnitDvOverview::class, [FinanceProcessorDvOverview::class, FinanceSupervisorDvOverview::class]],
    'finance_processor' => ['finance_processor', FinanceProcessorDvOverview::class, [RequestingUnitDvOverview::class, FinanceSupervisorDvOverview::class]],
    'finance_supervisor' => ['finance_supervisor', FinanceSupervisorDvOverview::class, [RequestingUnitDvOverview::class, FinanceProcessorDvOverview::class]],
]);

it('replaces the default Filament widgets with the welcome banner', function () {
    Role::findOrCreate('requesting_unit');
    $user = User::factory()->create(['name' => 'Juana Dela Cruz']);
    $user->assignRole('requesting_unit');

    $this->actingAs($user)
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSeeLivewire(WelcomeWidget::class)
        ->assertDontSeeLivewire(FilamentInfoWidget::class)
        ->assertDontSeeLivewire(AccountWidget::class);
});

it('greets a requesting unit user and lists returned vouchers that need edits', function () {
    Role::findOrCreate('requesting_unit');
    $office = Office::factory()->create(['name' => 'College of Engineering']);
    $user = User::factory()->create(['name' => 'Juana Dela Cruz', 'office_id' => $office->id]);
    $user->assignRole('requesting_unit');
    $returned = DisbursementVoucher::factory()->create(['created_by' => $user->id, 'status' => 'returned', 'dv_no' => 'DV-2026-042']);

    $this->actingAs($user);

    Livewire::test(WelcomeWidget::class)
        ->assertSee('Juana!')
        ->assertSee('Requesting Unit')
        ->assertSee('College of Engineering')
        ->assertSee('1 voucher was returned and needs your edits.')
        ->assertSee('DV-2026-042')
        ->assertSee(RequestingUnitDashboard::getUrl(['tableAction' => 'edit', 'tableActionRecord' => $returned->id]))
        ->assertSee('Create a voucher');
});

it('tells a finance processor how many vouchers are waiting', function () {
    Role::findOrCreate('finance_processor');
    $user = User::factory()->create();
    $user->assignRole('finance_processor');
    $stage = ProcessingStage::factory()->create(['name' => ProcessingStage::FINANCE_PROCESSOR]);
    DisbursementVoucher::factory()->count(2)->create(['status' => 'submitted', 'current_stage_id' => $stage->id]);

    $this->actingAs($user);

    Livewire::test(WelcomeWidget::class)
        ->assertSee('Finance Processor')
        ->assertSee('2 vouchers are waiting for your review.')
        ->assertSee('Open review queue');
});

it('opens the create form when the requesting unit page is visited with the create deep link', function () {
    Role::findOrCreate('requesting_unit');
    $user = User::factory()->create();
    $user->assignRole('requesting_unit');

    $this->actingAs($user);

    // Filament mounts `?tableAction=` on the client via `wire:init` once the
    // table has loaded, so assert the page renders that hook for "create".
    Livewire::withQueryParams(['tableAction' => 'create'])
        ->test(RequestingUnitDashboard::class)
        ->call('loadTable')
        ->assertSeeHtml("mountAction('create'");
});
