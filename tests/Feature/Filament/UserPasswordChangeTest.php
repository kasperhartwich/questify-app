<?php

use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('can change a user password from the view page', function () {
    $user = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->callAction('changePassword', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertHasNoActionErrors();

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

it('requires password confirmation to match', function () {
    $user = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->callAction('changePassword', [
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ])
        ->assertHasActionErrors(['password_confirmation' => 'same']);
});

it('requires a minimum password length of 8', function () {
    $user = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->callAction('changePassword', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertHasActionErrors(['password' => 'min']);
});
