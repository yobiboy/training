<?php

use App\Filament\Pages\RequestingUnitDashboard;
use App\Filament\Widgets\RequestingUnitDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\Fund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('forbids a user without the Requesting Unit role', function () {
    Role::findOrCreate('Finance Supervisor');
    $user = User::factory()->create();
    $user->assignRole('Finance Supervisor');

    $this->actingAs($user)
        ->get(RequestingUnitDashboard::getUrl())
        ->assertForbidden();
});

it('allows a user with the Requesting Unit role to view the dashboard', function () {
    Role::findOrCreate('Requesting Unit');
    $user = User::factory()->create();
    $user->assignRole('Requesting Unit');

    $this->actingAs($user)
        ->get(RequestingUnitDashboard::getUrl())
        ->assertOk();
});

it('submits a disbursement voucher owned by the authenticated requester', function () {
    Role::findOrCreate('Requesting Unit');
    $user = User::factory()->create();
    $user->assignRole('Requesting Unit');
    $fund = Fund::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user);

    Livewire::test(RequestingUnitDashboard::class)
        ->fillForm([
            'dv_no' => 'DV-TEST-0001',
            'payee_name' => 'Test Payee',
            'fund_id' => $fund->id,
            'amount' => 1000,
            'particulars' => 'Test particulars',
        ])
        ->call('createDisbursementVoucher')
        ->assertHasNoFormErrors();

    $voucher = DisbursementVoucher::where('dv_no', 'DV-TEST-0001')->first();

    expect($voucher)->not->toBeNull()
        ->and($voucher->status)->toBe('submitted')
        ->and($voucher->created_by)->toBe($user->id)
        ->and($voucher->submitted_at)->not->toBeNull();
});

it('only counts the authenticated requester\'s own vouchers in the stats widget', function () {
    Role::findOrCreate('Requesting Unit');
    $user = User::factory()->create();
    $user->assignRole('Requesting Unit');
    $otherUser = User::factory()->create();

    DisbursementVoucher::factory()->create(['created_by' => $user->id, 'status' => 'submitted']);
    DisbursementVoucher::factory()->create(['created_by' => $otherUser->id, 'status' => 'submitted']);

    $this->actingAs($user);

    Livewire::test(RequestingUnitDvOverview::class)
        ->assertSee('Submitted DVs')
        ->assertSee('1');
});
