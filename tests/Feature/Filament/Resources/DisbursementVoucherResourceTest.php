<?php

use App\Filament\Resources\DisbursementVouchers\Pages\ListDisbursementVouchers;
use App\Filament\Resources\DisbursementVouchers\Pages\ViewDisbursementVoucher;
use App\Models\DisbursementVoucher;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $superAdmin = Role::findOrCreate('super_admin');
    $superAdmin->givePermissionTo(collect(['ViewAny', 'View', 'Update', 'Restore', 'RestoreAny'])
        ->map(fn (string $ability) => Permission::findOrCreate("{$ability}:DisbursementVoucher")));
    $this->admin = User::factory()->create();
    $this->admin->assignRole($superAdmin);

    $requester = User::factory()->create();
    $this->deletedVoucher = DisbursementVoucher::factory()->create(['status' => 'draft', 'created_by' => $requester->id]);
    $this->actingAs($requester);
    $this->deletedVoucher->deleteByRequester();
});

it('lets the admin find vouchers deleted by a requesting unit', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListDisbursementVouchers::class)
        ->assertCanNotSeeTableRecords([$this->deletedVoucher])
        ->filterTable('trashed', true)
        ->assertCanSeeTableRecords([$this->deletedVoucher]);

    $this->get(ViewDisbursementVoucher::getUrl(['record' => $this->deletedVoucher]))
        ->assertOk()
        ->assertSee($this->deletedVoucher->dv_no);
});

it('lets the admin restore a deleted voucher', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListDisbursementVouchers::class)
        ->filterTable('trashed', true)
        ->callAction(TestAction::make('restore')->table($this->deletedVoucher));

    expect($this->deletedVoucher->fresh()->trashed())->toBeFalse();
});
