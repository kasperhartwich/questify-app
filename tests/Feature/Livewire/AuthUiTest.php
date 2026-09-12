<?php

use App\Auth\ApiTokenUser;
use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\UserApiResource;
use Illuminate\Support\Facades\Cache;
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
        ->set('email', 'new@example.com')
        ->call('goToEmailSignup')
        ->assertSet('step', 2)
        ->assertSee(__('auth.your_details'))
        ->assertSee(__('auth.first_name'))
        ->assertSee(__('auth.display_name'));
});

it('blocks email signup with duplicate email on step 1', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('pages::auth.register')
        ->set('email', 'taken@example.com')
        ->call('goToEmailSignup')
        ->assertSet('step', 1)
        ->assertHasErrors('email');
});

it('blocks email signup with invalid email on step 1', function () {
    Livewire::test('pages::auth.register')
        ->set('email', 'not-an-email')
        ->call('goToEmailSignup')
        ->assertSet('step', 1)
        ->assertHasErrors('email');
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
    $mockAuth = Mockery::mock(AuthResource::class);
    $mockAuth->shouldReceive('registerPhone')->andReturn([
        'requires_otp' => true,
        'login_token' => 'test-token',
    ]);
    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('auth')->andReturn($mockAuth);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());
    app()->instance(QuestifyApiClient::class, $mockClient);

    Livewire::test('pages::auth.register')
        ->set('country_code', '+45')
        ->set('phone_local', '12345678')
        ->call('sendPhoneCode')
        ->assertSet('step', 2)
        ->assertSet('signup_method', 'phone');
});

it('email signup does not show phone field', function () {
    Livewire::test('pages::auth.register')
        ->set('email', 'test@example.com')
        ->call('goToEmailSignup')
        ->assertSet('signup_method', 'email')
        ->assertDontSee(__('auth.phone_e164_hint'));
});

it('phone signup requires phone number', function () {
    Livewire::test('pages::auth.register')
        ->set('country_code', '+45')
        ->set('phone_local', '')
        ->call('sendPhoneCode')
        ->assertHasErrors('phone_local');
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

it('hides the tester button when the backend does not offer it', function () {
    Cache::put('app_info', ['data' => ['auth_methods' => [
        'email' => true, 'phone' => true, 'tester' => false,
    ]]]);

    Livewire::test('pages::auth.login')
        ->assertSet('testerEnabled', false)
        ->assertDontSee('Tester');
});

it('shows the tester button when the backend offers it', function () {
    Cache::put('app_info', ['data' => ['auth_methods' => [
        'email' => true, 'phone' => true, 'tester' => true,
    ]]]);

    Livewire::test('pages::auth.login')
        ->assertSet('testerEnabled', true)
        ->assertSee('Tester');
});

it('refuses tester login when the backend does not offer it', function () {
    Cache::put('app_info', ['data' => ['auth_methods' => [
        'email' => true, 'phone' => true, 'tester' => false,
    ]]]);

    Livewire::test('pages::auth.login')
        ->call('loginAsTester')
        ->assertNoRedirect();

    expect(auth()->check())->toBeFalse();
});

it('derives linked accounts from the authenticated user', function () {
    $user = new ApiTokenUser([
        'id' => 1, 'name' => 'Kasper', 'email' => 'k@example.com',
        'locale' => 'en', 'linked_providers' => ['apple'],
    ]);

    expect($user->linkedProviders)->toBe(['apple']);
});

it('defaults to no linked providers when the API omits them', function () {
    $user = new ApiTokenUser([
        'id' => 1, 'name' => 'Kasper', 'email' => 'k@example.com', 'locale' => 'en',
    ]);

    expect($user->linkedProviders)->toBe([]);
});

it('deletes the account through the API and signs the user out', function () {
    $deleted = false;

    $mockUserResource = Mockery::mock(UserApiResource::class);
    $mockUserResource->shouldReceive('deleteAccount')->once()->andReturnUsing(function () use (&$deleted) {
        $deleted = true;

        return ['message' => 'deleted'];
    });
    $mockUserResource->shouldReceive('quests')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);
    $mockUserResource->shouldReceive('sessions')->andReturn(['data' => []]);
    $mockUserResource->shouldReceive('favourites')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);

    $mockAuth = Mockery::mock(AuthResource::class);
    $mockAuth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Kasper', 'email' => 'k@example.com', 'locale' => 'en']]);
    $mockAuth->shouldReceive('logout')->andReturnNull();

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('user')->andReturn($mockUserResource);
    $mockClient->shouldReceive('auth')->andReturn($mockAuth);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());
    app()->instance(QuestifyApiClient::class, $mockClient);

    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    $user = new ApiTokenUser(['id' => 1, 'name' => 'Kasper', 'email' => 'k@example.com', 'locale' => 'en']);

    Livewire::actingAs($user)
        ->test('pages::profile.settings')
        ->call('deleteAccount')
        ->assertRedirect('/');

    expect($deleted)->toBeTrue()
        ->and(auth()->check())->toBeFalse();
});
