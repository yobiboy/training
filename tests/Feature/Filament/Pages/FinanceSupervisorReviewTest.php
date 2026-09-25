<?php

use App\Filament\Pages\FinanceSupervisorReview;
use App\Filament\Widgets\FinanceSupervisorDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('finance_supervisor');
    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole('finance_supervisor');
    $this->processorStage = ProcessingStage::factory()->create(['name' => ProcessingStage::FINANCE_PROCESSOR, 'sequence' => 1]);
    $this->supervisorStage = ProcessingStage::factory()->create(['name' => ProcessingStage::SUPERVISOR, 'sequence' => 2]);
});

it('forbids a user without the finance_supervisor role', function () {
    Role::findOrCreate('finance_processor');
    $user = User::factory()->create();
    $user->assignRole('finance_processor');

    $this->actingAs($user)
        ->get(FinanceSupervisorReview::getUrl())
        ->assertForbidden();
});

it('allows a finance_supervisor to view the page', function () {
    $this->actingAs($this->supervisor)
        ->get(FinanceSupervisorReview::getUrl())
        ->assertOk();
});

it('only lists for-payment vouchers at the Supervisor stage', function () {
    $forwarded = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->supervisorStage->id]);
    $atProcessor = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);
    $completed = DisbursementVoucher::factory()->create(['status' => 'completed', 'current_stage_id' => $this->supervisorStage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->assertCanSeeTableRecords([$forwarded])
        ->assertCanNotSeeTableRecords([$atProcessor, $completed]);
});

it('marks a forwarded voucher completed and stamps the completion date', function () {
    $this->freezeSecond();
    $voucher = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->supervisorStage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->callAction(TestAction::make('markCompleted')->table($voucher))
        ->assertCanNotSeeTableRecords([$voucher]);

    expect($voucher->refresh())
        ->status->toBe('completed')
        ->completed_at->toEqual(now());

    expect(RoutingHistory::where('disbursement_voucher_id', $voucher->id)->sole())
        ->action->toBe('completed')
        ->processing_stage_id->toBe($this->supervisorStage->id)
        ->acted_by->toBe($this->supervisor->id);
});

it('shows voucher details in the view action', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->supervisorStage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->mountAction(TestAction::make('view')->table($voucher))
        ->assertMountedActionModalSee($voucher->dv_no)
        ->assertMountedActionModalSee($voucher->particulars);
});

it('summarises the supervisor queue and completions in the stats widget', function () {
    DisbursementVoucher::factory()->count(2)->create(['status' => 'for_payment', 'current_stage_id' => $this->supervisorStage->id, 'amount' => 2500]);
    DisbursementVoucher::factory()->create(['status' => 'completed', 'current_stage_id' => $this->supervisorStage->id, 'completed_at' => now(), 'amount' => 700]);
    DisbursementVoucher::factory()->create(['status' => 'completed', 'current_stage_id' => $this->supervisorStage->id, 'completed_at' => now()->subMonths(2), 'amount' => 99999]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorDvOverview::class)
        ->assertSeeInOrder(['Awaiting completion', '2'])
        ->assertSeeInOrder(['Pending payment amount', '5,000.00'])
        ->assertSeeInOrder(['Completed today', '1'])
        ->assertSeeInOrder(['Completed this month', '1', '700.00 disbursed']);
});

it('hides the supervisor stats widget from other roles', function () {
    Role::findOrCreate('finance_processor');
    $user = User::factory()->create();
    $user->assignRole('finance_processor');

    $this->actingAs($user);

    expect(FinanceSupervisorDvOverview::canView())->toBeFalse();
});
