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

it('signup step 1 shows email and continue button', function () {
    Livewire::test('pages::auth.register')
        ->assertSet('step', 1)
        ->assertSee(__('general.email'))
        ->assertSee(__('auth.continue'));
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

it('signup step 1 shows phone option', function () {
    Livewire::test('pages::auth.register')
        ->assertSet('step', 1)
        ->assertSee(__('general.phone'));
});

it('clicking phone signup advances to step 2 with phone field', function () {
    Livewire::test('pages::auth.register')
        ->call('goToPhoneSignup')
        ->assertSet('step', 2)
        ->assertSet('signup_method', 'phone')
        ->assertSee(__('auth.phone_number'))
        ->assertSee(__('auth.phone_sms_disclaimer'));
});

it('email signup does not show phone field', function () {
    Livewire::test('pages::auth.register')
        ->call('goToEmailSignup')
        ->assertSet('signup_method', 'email')
        ->assertDontSee(__('auth.phone_e164_hint'));
});

it('phone signup requires phone number', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->set('signup_method', 'phone')
        ->set('first_name', 'Anna')
        ->set('display_name', 'AdventureAnna')
        ->set('email', 'anna@example.com')
        ->set('password', 'password123')
        ->set('phone_number', '')
        ->call('register')
        ->assertHasErrors('phone_number');
});

it('phone signup validates E.164 format', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 2)
        ->set('signup_method', 'phone')
        ->set('first_name', 'Anna')
        ->set('display_name', 'AdventureAnna')
        ->set('email', 'anna@example.com')
        ->set('password', 'password123')
        ->set('phone_number', '12345')
        ->call('register')
        ->assertHasErrors('phone_number');
});

it('phone verify step requires 6-digit code', function () {
    Livewire::test('pages::auth.register')
        ->set('step', 3)
        ->set('phone_code', '123')
        ->call('verifyPhone')
        ->assertHasErrors('phone_code');
});

it('login OTP step renders when step is otp', function () {
    Livewire::test('pages::auth.login')
        ->set('step', 'otp')
        ->set('login_token', 'test-token')
        ->set('email', 'test@example.com')
        ->assertSee(__('auth.verify_login'))
        ->assertSee(__('auth.enter_6_digit_code'));
});

it('login OTP requires 6-digit code', function () {
    Livewire::test('pages::auth.login')
        ->set('step', 'otp')
        ->set('login_token', 'test-token')
        ->set('otp_code', '12')
        ->call('verifyOtp')
        ->assertHasErrors('otp_code');
});

it('login back to login resets OTP state', function () {
    Livewire::test('pages::auth.login')
        ->set('step', 'otp')
        ->set('login_token', 'some-token')
        ->set('otp_code', '123456')
        ->call('backToLogin')
        ->assertSet('step', 'login')
        ->assertSet('otp_code', '')
        ->assertSet('login_token', '');
});
