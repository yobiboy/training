<?php

use App\Filament\Pages\FinanceProcessorReview;
use App\Models\DisbursementVoucher;
use App\Models\Office;
use App\Models\ProcessingStage;
use App\Models\RoutingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('Finance Processor');
    $this->processor = User::factory()->create();
    $this->processor->assignRole('Finance Processor');
});

it('forbids a user without the Finance Processor role', function () {
    Role::findOrCreate('Requesting Unit');
    $user = User::factory()->create();
    $user->assignRole('Requesting Unit');

    $this->actingAs($user)
        ->get(FinanceProcessorReview::getUrl())
        ->assertForbidden();
});

it('allows a Finance Processor to view the page', function () {
    $this->actingAs($this->processor)
        ->get(FinanceProcessorReview::getUrl())
        ->assertOk();
});

it('only lists submitted vouchers', function () {
    $submitted = DisbursementVoucher::factory()->create(['status' => 'submitted']);
    $completed = DisbursementVoucher::factory()->create(['status' => 'completed']);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->assertCanSeeTableRecords([$submitted])
        ->assertCanNotSeeTableRecords([$completed]);
});

it('filters submitted vouchers by the requester\'s office', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $requesterA = User::factory()->create(['office_id' => $officeA->id]);
    $requesterB = User::factory()->create(['office_id' => $officeB->id]);
    $voucherA = DisbursementVoucher::factory()->create(['status' => 'submitted', 'created_by' => $requesterA->id]);
    $voucherB = DisbursementVoucher::factory()->create(['status' => 'submitted', 'created_by' => $requesterB->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->filterTable('office', $officeA->id)
        ->assertCanSeeTableRecords([$voucherA])
        ->assertCanNotSeeTableRecords([$voucherB]);
});

it('endorses a voucher to the first stage in the workflow, regardless of its name', function () {
    $firstStage = ProcessingStage::factory()->create(['sequence' => 1]);
    ProcessingStage::factory()->create(['sequence' => 2]);
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => null]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('endorse', $voucher);

    $voucher->refresh();

    expect($voucher->status)->toBe('in_process')
        ->and($voucher->current_stage_id)->toBe($firstStage->id);

    $history = RoutingHistory::where('disbursement_voucher_id', $voucher->id)->first();

    expect($history->action)->toBe('forwarded')
        ->and($history->processing_stage_id)->toBe($firstStage->id);
});

it('endorses a voucher already mid-workflow to the next stage by sequence', function () {
    $currentStage = ProcessingStage::factory()->create(['sequence' => 1]);
    $nextStage = ProcessingStage::factory()->create(['sequence' => 2]);
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted', 'current_stage_id' => $currentStage->id]);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('endorse', $voucher);

    expect($voucher->fresh()->current_stage_id)->toBe($nextStage->id);
});

it('returns a voucher with the given remarks', function () {
    ProcessingStage::factory()->create();
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted']);

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
    ProcessingStage::factory()->create();
    $voucher = DisbursementVoucher::factory()->create(['status' => 'submitted']);

    $this->actingAs($this->processor);

    Livewire::test(FinanceProcessorReview::class)
        ->callTableAction('return', $voucher, data: ['remarks' => ''])
        ->assertHasTableActionErrors(['remarks' => 'required']);

    expect($voucher->fresh()->status)->toBe('submitted');
});
