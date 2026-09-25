<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('forbids a user without the ViewAny:Role permission from viewing the roles list', function () {
    $role = Role::findOrCreate('requesting_unit');
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('/admin/shield/roles')
        ->assertForbidden();
});

it('allows a super_admin with the ViewAny:Role permission to view the roles list', function () {
    Permission::findOrCreate('ViewAny:Role');
    $superAdmin = Role::findOrCreate('super_admin');
    $superAdmin->givePermissionTo('ViewAny:Role');

    $user = User::factory()->create();
    $user->assignRole($superAdmin);

    $this->actingAs($user)
        ->get('/admin/shield/roles')
        ->assertOk();
});
