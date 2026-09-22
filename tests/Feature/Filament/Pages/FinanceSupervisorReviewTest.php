<?php

use App\Filament\Pages\FinanceSupervisorReview;
use App\Models\DisbursementVoucher;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('Finance Supervisor');
    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole('Finance Supervisor');
    // A voucher only reaches "in_process" via the Finance Processor's Endorse
    // action, which always assigns a stage — so tests mirror that here too.
    $this->stage = ProcessingStage::factory()->create();
});

it('forbids a user without the Finance Supervisor role', function () {
    Role::findOrCreate('Finance Processor');
    $user = User::factory()->create();
    $user->assignRole('Finance Processor');

    $this->actingAs($user)
        ->get(FinanceSupervisorReview::getUrl())
        ->assertForbidden();
});

it('allows a Finance Supervisor to view the page', function () {
    $this->actingAs($this->supervisor)
        ->get(FinanceSupervisorReview::getUrl())
        ->assertOk();
});

it('lists both in-process and for-payment vouchers, but not submitted or completed ones', function () {
    $inProcess = DisbursementVoucher::factory()->create(['status' => 'in_process', 'current_stage_id' => $this->stage->id]);
    $forPayment = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->stage->id]);
    $submitted = DisbursementVoucher::factory()->create(['status' => 'submitted']);
    $completed = DisbursementVoucher::factory()->create(['status' => 'completed']);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->assertCanSeeTableRecords([$inProcess, $forPayment])
        ->assertCanNotSeeTableRecords([$submitted, $completed]);
});

it('marks an in-process voucher for payment', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'in_process', 'current_stage_id' => $this->stage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->callTableAction('markForPayment', $voucher);

    $voucher->refresh();

    expect($voucher->status)->toBe('for_payment')
        ->and($voucher->completed_at)->toBeNull();

    $history = RoutingHistory::where('disbursement_voucher_id', $voucher->id)->first();

    expect($history->action)->toBe('processed');
});

it('marks a for-payment voucher completed and stamps the completion time', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->stage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->callTableAction('markCompleted', $voucher);

    $voucher->refresh();

    expect($voucher->status)->toBe('completed')
        ->and($voucher->completed_at)->not->toBeNull();

    $history = RoutingHistory::where('disbursement_voucher_id', $voucher->id)->first();

    expect($history->action)->toBe('processed');
});

it('hides the Mark for Payment action once a voucher is already for payment', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->stage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->assertTableActionHidden('markForPayment', $voucher);
});

it('hides the Mark Completed action while a voucher is still in process', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'in_process', 'current_stage_id' => $this->stage->id]);

    $this->actingAs($this->supervisor);

    Livewire::test(FinanceSupervisorReview::class)
        ->assertTableActionHidden('markCompleted', $voucher);
});
