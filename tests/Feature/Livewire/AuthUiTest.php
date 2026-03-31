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

it('signup step 1 shows social buttons and email signup option', function () {
    Livewire::test('pages::auth.register')
        ->assertSet('step', 1)
        ->assertSee('Google')
        ->assertSee('Facebook')
        ->assertSee('Apple')
        ->assertSee('Microsoft')
        ->assertSeeHtml('/auth/google/redirect')
        ->assertSee(__('auth.signup_with_email'));
});

it('clicking email signup advances to step 2', function () {
    Livewire::test('pages::auth.register')
        ->assertSet('step', 1)
        ->call('goToEmailSignup')
        ->assertSet('step', 2)
        ->assertSee(__('auth.your_details'))
        ->assertSee(__('auth.first_name'))
        ->assertSee(__('auth.display_name'));
});

it('step 2 back button returns to step 1', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->call('goBack')
        ->assertSet('step', 1);
});

it('register requires first name and display name', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->set('first_name', '')
        ->set('display_name', '')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->call('register')
        ->assertHasErrors(['first_name', 'display_name']);
});

it('register requires a valid email', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->set('first_name', 'Anna')
        ->set('display_name', 'AdventureAnna')
        ->set('email', 'not-an-email')
        ->set('password', 'password123')
        ->call('register')
        ->assertHasErrors('email');
});
