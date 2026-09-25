<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('renders the split-screen sign-in page', function () {
    $this->get(Filament::getLoginUrl())
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Every voucher,', escape: false)
        ->assertSee('dv-login-aside', escape: false);
});

it('only lists the demo accounts in the local environment', function () {
    $this->get(Filament::getLoginUrl())
        ->assertDontSee('Demo accounts');

    app()->detectEnvironment(fn (): string => 'local');

    $this->get(Filament::getLoginUrl())
        ->assertSee('Demo accounts')
        ->assertSee('requester@example.com');
});

it('still signs a user in', function () {
    Role::findOrCreate('requesting_unit');
    $user = User::factory()->create(['password' => 'secret-password']);
    $user->assignRole('requesting_unit');

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'secret-password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getUrl());

    $this->assertAuthenticatedAs($user);
});
