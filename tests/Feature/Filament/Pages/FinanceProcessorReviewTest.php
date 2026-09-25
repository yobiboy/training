<?php

use App\Filament\Pages\FinanceProcessorReview;
use App\Filament\Widgets\FinanceProcessorDvOverview;
use App\Models\DisbursementVoucher;
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
    Role::findOrCreate('finance_processor');
    $this->processor = User::factory()->create();
    $this->processor->assignRole('finance_processor');
    $this->processorStage = ProcessingStage::factory()->create(['name' => ProcessingStage::FINANCE_PROCESSOR, 'sequence' => 1]);
    $this->supervisorStage = ProcessingStage::factory()->create(['name' => ProcessingStage::SUPERVISOR, 'sequence' => 2]);
});

it('forbids a user without the finance_processor role', function () {
    Role::findOrCreate('requesting_unit');
    $user = User::factory()->create();
    $user->assignRole('requesting_unit');

    $this->actingAs($user)
        ->get(FinanceProcessorReview::getUrl())
        ->assertForbidden();
});

it('allows a finance_processor to view the page', function () {
    $this->actingAs($this->processor)
        ->get(FinanceProcessorReview::getUrl())
        ->assertOk();
});

it('only lists submitted vouchers at the Finance Processor stage', function () {
    $submitted = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);
    $atOtherStage = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->supervisorStage->id]);
    $draft = DisbursementVoucher::factory()->create(['status' => 'draft', 'current_stage_id' => null]);
    $completed = DisbursementVoucher::factory()->create(['status' => 'completed', 'current_stage_id' => $this->processorStage->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->assertCanSeeTableRecords([$submitted])
        ->assertCanNotSeeTableRecords([$atOtherStage, $draft, $completed]);
});

it('filters submitted vouchers by the requester\'s office', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $requesterA = User::factory()->create(['office_id' => $officeA->id]);
    $requesterB = User::factory()->create(['office_id' => $officeB->id]);
    $voucherA = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id, 'created_by' => $requesterA->id]);
    $voucherB = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id, 'created_by' => $requesterB->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->filterTable('office', $officeA->id)
        ->assertCanSeeTableRecords([$voucherA])
        ->assertCanNotSeeTableRecords([$voucherB]);
});

it('forwards a voucher to the Supervisor stage and marks it for payment', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('forward', $voucher);

    expect($voucher->refresh())
        ->status->toBe('for_payment')
        ->current_stage_id->toBe($this->supervisorStage->id);

    expect(RoutingHistory::where('disbursement_voucher_id', $voucher->id)->sole())
        ->action->toBe('forwarded')
        ->processing_stage_id->toBe($this->supervisorStage->id)
        ->acted_by->toBe($this->processor->id);
});

it('returns a voucher with the given remarks', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('return', $voucher, data: ['remarks' => 'Missing supporting documents'])
        ->assertHasNoTableActionErrors();

    $voucher->refresh();

    expect($voucher->status)->toBe('returned')
        ->and($voucher->current_stage_id)->toBeNull();

    $history = RoutingHistory::where('disbursement_voucher_id', $voucher->id)->first();

    expect($history->action)->toBe('returned')
        ->and($history->remarks)->toBe('Missing supporting documents');
});

it('requires remarks to return a voucher', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('return', $voucher, data: ['remarks' => ''])
        ->assertHasTableActionErrors(['remarks' => 'required']);

    expect($voucher->fresh()->status)->toBe('submitted');
});

it('shows voucher details and routing history in the view action', function () {
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id]);

    $this->actingAs($this->processor);

    RoutingHistory::create([
        'disbursement_voucher_id' => $voucher->id,
        'processing_stage_id' => $this->processorStage->id,
        'action' => 'submitted',
        'remarks' => 'Initial submission note',
        'acted_at' => now(),
    ]);

    Livewire::test(FinanceProcessorReview::class)
        ->mountAction(TestAction::make('view')->table($voucher))
        ->assertMountedActionModalSee($voucher->dv_no)
        ->assertMountedActionModalSee($voucher->particulars)
        ->assertMountedActionModalSee('Initial submission note');
});

it('summarises the processor queue and this month\'s activity in the stats widget', function () {
    DisbursementVoucher::factory()->count(2)->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id, 'amount' => 1500]);
    $resubmitted = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $this->processorStage->id, 'amount' => 1000]);
    DisbursementVoucher::factory()->create(['status' => 'for_payment', 'current_stage_id' => $this->supervisorStage->id, 'amount' => 99999]);

    $this->actingAs($this->processor);

    RoutingHistory::create(['disbursement_voucher_id' => $resubmitted->id, 'processing_stage_id' => $this->processorStage->id, 'action' => 'resubmitted', 'acted_at' => now()]);
    RoutingHistory::create(['disbursement_voucher_id' => $resubmitted->id, 'processing_stage_id' => $this->supervisorStage->id, 'action' => 'forwarded', 'acted_at' => now()]);
    RoutingHistory::create(['disbursement_voucher_id' => $resubmitted->id, 'processing_stage_id' => $this->supervisorStage->id, 'action' => 'forwarded', 'acted_at' => now()->subMonths(2)]);

    Livewire::test(FinanceProcessorDvOverview::class)
        ->assertSeeInOrder(['Awaiting review', '3', '4,000.00 total'])
        ->assertSeeInOrder(['Resubmitted after return', '1'])
        ->assertSeeInOrder(['Forwarded this month', '1']);
});

it('hides the processor stats widget from other roles', function () {
    Role::findOrCreate('requesting_unit');
    $user = User::factory()->create();
    $user->assignRole('requesting_unit');

    $this->actingAs($user);

    expect(FinanceProcessorDvOverview::canView())->toBeFalse();
});
