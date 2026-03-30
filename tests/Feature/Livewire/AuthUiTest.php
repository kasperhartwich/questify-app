<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the login page', function () {
    $this->get('/login')->assertOk();
});

it('renders the registration page', function () {
    $this->get('/register')->assertOk();
});

it('can log in with valid credentials via Livewire', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect('/discover/list');

    $this->assertAuthenticatedAs($user);
});

it('shows validation error for wrong password on login', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('shows validation error for empty email on login', function () {
    Livewire::test('pages::auth.login')
        ->set('email', '')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('email');
});

it('can register with valid data via Livewire', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/discover/list');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});

it('shows validation error for duplicate email on register', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('pages::auth.register')
        ->set('name', 'New User')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors('email');
});

it('shows validation error for short password on register', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('register')
        ->assertHasErrors('password');
});

it('login page contains register link', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('/register');
});

it('register page contains login link', function () {
    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('/login');
});
