<?php

use App\Filament\Pages\RequestingUnitDashboard;
use App\Filament\Widgets\RequestingUnitDvOverview;
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
    Role::findOrCreate('requesting_unit');
    $this->office = Office::factory()->create();
    $this->requester = User::factory()->create(['office_id' => $this->office->id]);
    $this->requester->assignRole('requesting_unit');
    $this->fund = Fund::factory()->create(['created_by' => $this->requester->id]);
    $this->financeProcessorStage = ProcessingStage::factory()->create(['name' => 'Finance Processor', 'sequence' => 1]);

    $this->voucherData = [
        'payee_name' => 'Test Payee',
        'fund_id' => $this->fund->id,
        'amount' => 1000,
        'particulars' => 'Test particulars',
    ];
});

it('forbids a user without the requesting_unit role', function () {
    Role::findOrCreate('finance_supervisor');
    $user = User::factory()->create();
    $user->assignRole('finance_supervisor');

    $this->actingAs($user)
        ->get(RequestingUnitDashboard::getUrl())
        ->assertForbidden();
});

it('allows a user with the requesting_unit role to view the dashboard', function () {
    $this->actingAs($this->requester)
        ->get(RequestingUnitDashboard::getUrl())
        ->assertOk();
});

it('lists only vouchers from the requester\'s office', function () {
    $officemate = User::factory()->create(['office_id' => $this->office->id]);
    $outsider = User::factory()->create(['office_id' => Office::factory()->create()->id]);

    $ownVoucher = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id]);
    $officemateVoucher = DisbursementVoucher::factory()->create(['created_by' => $officemate->id]);
    $outsiderVoucher = DisbursementVoucher::factory()->create(['created_by' => $outsider->id]);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->assertCanSeeTableRecords([$ownVoucher, $officemateVoucher])
        ->assertCanNotSeeTableRecords([$outsiderVoucher]);
});

it('saves a new voucher as a draft with a generated DV number', function () {
    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('create')->table(), data: $this->voucherData, arguments: ['draft' => true])
        ->assertHasNoFormErrors();

    $voucher = DisbursementVoucher::sole();

    expect($voucher->dv_no)->toBe('DV-'.now()->year.'-001')
        ->and($voucher->status)->toBe('draft')
        ->and($voucher->current_stage_id)->toBeNull()
        ->and($voucher->submitted_at)->toBeNull()
        ->and($voucher->created_by)->toBe($this->requester->id);
});

it('submits a new voucher to the Finance Processor stage', function () {
    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('create')->table(), data: $this->voucherData)
        ->assertHasNoFormErrors();

    $voucher = DisbursementVoucher::sole();

    expect($voucher->status)->toBe('submitted')
        ->and($voucher->current_stage_id)->toBe($this->financeProcessorStage->id)
        ->and($voucher->submitted_at)->not->toBeNull();

    expect(RoutingHistory::sole())
        ->disbursement_voucher_id->toBe($voucher->id)
        ->processing_stage_id->toBe($this->financeProcessorStage->id)
        ->action->toBe('submitted');
});

it('submits an existing draft', function () {
    $draft = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'draft']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('submit')->table($draft));

    expect($draft->refresh())
        ->status->toBe('submitted')
        ->current_stage_id->toBe($this->financeProcessorStage->id);
});

it('updates a draft without submitting it', function () {
    $draft = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'draft']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('edit')->table($draft), data: ['payee_name' => 'Updated Payee'], arguments: ['draft' => true])
        ->assertHasNoFormErrors();

    expect($draft->refresh())
        ->payee_name->toBe('Updated Payee')
        ->status->toBe('draft');
});

it('hides edit and submit actions on vouchers that are already submitted', function () {
    $submitted = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'submitted']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->assertActionHidden(TestAction::make('edit')->table($submitted))
        ->assertActionHidden(TestAction::make('submit')->table($submitted));
});

it('only counts vouchers from the requester\'s office in the stats widget', function () {
    $outsider = User::factory()->create(['office_id' => Office::factory()->create()->id]);

    DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'submitted']);
    DisbursementVoucher::factory()->create(['created_by' => $outsider->id, 'status' => 'submitted']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDvOverview::class)
        ->assertSee('Submitted DVs')
        ->assertSee('1');
});

it('shows voucher details in the view action', function () {
    $voucher = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'submitted']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->mountAction(TestAction::make('view')->table($voucher))
        ->assertMountedActionModalSee($voucher->dv_no)
        ->assertMountedActionModalSee($voucher->particulars)
        ->assertMountedActionModalSee('No routing activity yet.');
});

it('resubmits a returned voucher through the edit form, keeping its DV number', function () {
    $returned = DisbursementVoucher::factory()->create([
        'created_by' => $this->requester->id,
        'dv_no' => 'DV-2026-007',
        'status' => 'returned',
        'current_stage_id' => null,
    ]);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->assertActionHidden(TestAction::make('submit')->table($returned))
        ->assertActionVisible(TestAction::make('edit')->table($returned))
        ->callAction(TestAction::make('edit')->table($returned), data: ['payee_name' => 'Corrected Payee'])
        ->assertHasNoFormErrors();

    expect($returned->refresh())
        ->dv_no->toBe('DV-2026-007')
        ->payee_name->toBe('Corrected Payee')
        ->status->toBe('submitted')
        ->current_stage_id->toBe($this->financeProcessorStage->id);

    expect(RoutingHistory::where('disbursement_voucher_id', $returned->id)->sole())
        ->action->toBe('resubmitted');
});

it('keeps a returned voucher returned when its edits are only saved', function () {
    $returned = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'returned']);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('edit')->table($returned), data: ['payee_name' => 'Corrected Payee'], arguments: ['draft' => true])
        ->assertHasNoFormErrors();

    expect($returned->refresh())
        ->payee_name->toBe('Corrected Payee')
        ->status->toBe('returned');
});

it('shows the return remarks when editing a returned voucher', function () {
    $returned = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => 'returned']);

    $this->actingAs($this->requester);

    RoutingHistory::create([
        'disbursement_voucher_id' => $returned->id,
        'processing_stage_id' => $this->financeProcessorStage->id,
        'action' => 'returned',
        'remarks' => 'Missing supporting documents',
        'acted_at' => now(),
    ]);

    Livewire::test(RequestingUnitDashboard::class)
        ->mountAction(TestAction::make('edit')->table($returned))
        ->assertMountedActionModalSee('Missing supporting documents');
});

it('soft deletes a draft or returned voucher and records who deleted it', function (string $status) {
    $voucher = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => $status]);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->callAction(TestAction::make('delete')->table($voucher))
        ->assertCanNotSeeTableRecords([$voucher]);

    expect(DisbursementVoucher::withTrashed()->find($voucher->id))
        ->trashed()->toBeTrue()
        ->updated_by->toBe($this->requester->id);
})->with(['draft', 'returned']);

it('hides the delete action once a voucher is in the Finance workflow', function (string $status) {
    $voucher = DisbursementVoucher::factory()->create(['created_by' => $this->requester->id, 'status' => $status]);

    $this->actingAs($this->requester);

    Livewire::test(RequestingUnitDashboard::class)
        ->assertActionHidden(TestAction::make('delete')->table($voucher));

    expect(fn () => $voucher->deleteByRequester())->toThrow(LogicException::class);
    expect($voucher->fresh()->trashed())->toBeFalse();
})->with(['submitted', 'for_payment', 'completed']);
