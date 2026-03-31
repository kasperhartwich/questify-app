<?php

use Livewire\Livewire;

it('renders the login page', function () {
    $this->get('/login')->assertOk();
});

it('renders the registration page', function () {
    $this->get('/register')->assertOk();
});

it('shows validation error for empty email on login', function () {
    Livewire::test('pages::auth.login')
        ->set('email', '')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('email');
});

it('shows validation error for short password on register', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->set('first_name', 'New')
        ->set('display_name', 'NewUser')
        ->set('email', 'new@example.com')
        ->set('password', 'short')
        ->call('register')
        ->assertHasErrors('password');
});

it('login page contains register link', function () {
    $this->get('/login')->assertOk()->assertSee('/register');
});

it('register page contains login link', function () {
    $this->get('/register')->assertOk()->assertSee('/login');
});
