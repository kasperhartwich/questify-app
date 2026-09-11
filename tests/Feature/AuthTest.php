<?php

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\AppInfoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('registers a new user', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['user', 'token'], 'message']);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('fails registration with short password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('fails registration with missing fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['user', 'token'], 'message']);
});

it('fails login with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertUnauthorized();
});

it('fails login with non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertUnauthorized();
});

it('logs out authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->postJson('/api/v1/auth/logout');

    $response->assertOk();
    expect($user->tokens()->count())->toBe(0);
});

it('prevents logout for unauthenticated user', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});

it('sends forgot password link', function () {
    $user = User::factory()->create();

    Password::shouldReceive('sendResetLink')
        ->once()
        ->andReturn('passwords.sent');

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk();
});

it('fails forgot password with invalid email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('resets password with valid token', function () {
    $user = User::factory()->create();

    Password::shouldReceive('reset')
        ->once()
        ->andReturn('passwords.reset');

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'valid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk();
});

it('fails reset password with invalid token', function () {
    $user = User::factory()->create();

    Password::shouldReceive('reset')
        ->once()
        ->andReturn('passwords.token');

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422);
});

it('returns authenticated user via me endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.name', $user->name);
});

it('prevents unauthenticated access to me endpoint', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

it('unlinks a social provider', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => SocialProvider::Google,
        'provider_id' => '12345',
        'name' => 'Test User',
        'email' => $user->email,
        'token' => 'some-token',
    ]);

    $response = $this->actingAs($user)->deleteJson('/api/v1/auth/social/google');

    $response->assertOk()
        ->assertJsonPath('message', 'Social account unlinked successfully.');

    $this->assertDatabaseMissing('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'google',
    ]);
});

it('returns 404 when unlinking provider not linked', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/api/v1/auth/social/google');

    $response->assertNotFound();
});

it('redirects to social provider', function () {
    config(['services.google.client_id' => 'test-client-id']);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn(Mockery::mock()->shouldReceive('redirect')
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'))
            ->getMock());

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirect();
});

it('rejects invalid social provider', function () {
    $response = $this->get('/auth/invalid/redirect');

    $response->assertStatus(302);
});

it('returns 404 for a provider without credentials', function (string $provider) {
    $response = $this->get("/auth/{$provider}/redirect");

    $response->assertNotFound();
})->with(['google', 'facebook', 'apple', 'microsoft']);

it('returns 404 for a provider without a socialite driver even when credentials are set', function () {
    config(['services.apple.client_id' => 'com.focusweb.questify']);

    $response = $this->get('/auth/apple/redirect');

    $response->assertNotFound();
});

it('returns 404 on callback for an unavailable provider', function () {
    $response = $this->get('/auth/apple/callback');

    $response->assertNotFound();
});

it('returns 404 for a configured provider disabled by the backend', function () {
    config(['services.google.client_id' => 'test-client-id']);
    Cache::put('app_info', [
        'data' => [
            'auth_methods' => [
                'email' => true,
                'phone' => true,
                'google' => false,
                'facebook' => false,
                'apple' => false,
                'microsoft' => false,
            ],
        ],
    ]);

    $response = $this->get('/auth/google/redirect');

    $response->assertNotFound();
});

it('only lists social providers the backend enables', function () {
    Cache::put('app_info', [
        'data' => [
            'auth_methods' => [
                'email' => true,
                'phone' => true,
                'google' => true,
                'facebook' => false,
                'apple' => true,
                'microsoft' => false,
            ],
        ],
    ]);

    expect(app(AppInfoService::class)->enabledSocialProviders())
        ->toBe(['google', 'apple']);
});

it('fetches backend info on a cache miss to decide social providers', function () {
    // No cache seeded — the service must fetch /info (faked in Pest.php with
    // all providers enabled) instead of guessing.
    expect(app(AppInfoService::class)->enabledSocialProviders())
        ->toBe(['google', 'facebook', 'apple', 'microsoft']);
});

it('builds the backend social redirect url', function () {
    config(['services.questify.url' => 'https://questifyapp.net']);

    expect(app(AppInfoService::class)->socialRedirectUrl('apple'))
        ->toBe('https://questifyapp.net/auth/apple/redirect');
});

it('completes social login via the deep link callback', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();
    mockFullApiClient();

    $response = $this->get('/auth/callback?token=test-token');

    $response->assertRedirect('/discover/list');
    expect(auth()->check())->toBeTrue();
});

it('redirects to login when the deep link callback has no token', function () {
    $this->get('/auth/callback')->assertRedirect('/login');
});

it('honours an internal redirect target on the deep link callback', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();
    mockFullApiClient();

    $this->get('/auth/callback?token=test-token&redirect=/quests/1')
        ->assertRedirect('/quests/1');
});

it('ignores external redirect targets on the deep link callback', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();
    mockFullApiClient();

    $this->get('/auth/callback?token=test-token&redirect=https://evil.example')
        ->assertRedirect('/discover/list');

    $this->get('/auth/callback?token=test-token&redirect=//evil.example')
        ->assertRedirect('/discover/list');
});

it('rejects an invalid deep link token', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    $mockAuth = Mockery::mock(AuthResource::class);
    $mockAuth->shouldReceive('me')->andThrow(new RuntimeException('Unauthenticated.'));
    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('auth')->andReturn($mockAuth);
    app()->instance(QuestifyApiClient::class, $mockClient);

    $this->get('/auth/callback?token=bad-token')->assertRedirect('/login');
    expect(auth()->check())->toBeFalse();
});

it('handles social auth callback for new user', function () {
    config(['services.google.client_id' => 'test-client-id']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('12345');
    $socialiteUser->shouldReceive('getName')->andReturn('Social User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('social@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    $socialiteUser->token = 'social-token';
    $socialiteUser->refreshToken = 'refresh-token';
    $socialiteUser->expiresIn = 3600;

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn(Mockery::mock()->shouldReceive('user')->andReturn($socialiteUser)->getMock());

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect();
    $this->assertStringStartsWith('questify://auth/callback?token=', $response->headers->get('Location'));

    $this->assertDatabaseHas('users', ['email' => 'social@example.com']);
    $this->assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_id' => '12345',
    ]);
});

it('handles social auth callback for existing user', function () {
    config(['services.google.client_id' => 'test-client-id']);

    $user = User::factory()->create(['email' => 'existing@example.com']);
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => SocialProvider::Google,
        'provider_id' => '12345',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'token' => 'old-token',
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('12345');
    $socialiteUser->shouldReceive('getName')->andReturn('Updated Name');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');
    $socialiteUser->token = 'new-token';
    $socialiteUser->refreshToken = null;
    $socialiteUser->expiresIn = null;

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn(Mockery::mock()->shouldReceive('user')->andReturn($socialiteUser)->getMock());

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect();
    $this->assertStringStartsWith('questify://auth/callback?token=', $response->headers->get('Location'));

    expect(SocialAccount::where('provider_id', '12345')->first()->token)->toBe('new-token');
    expect(User::count())->toBe(1);
});
