<?php

use App\Auth\ApiTokenUser;
use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiAuthenticationException;
use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\TokenStorage;

/**
 * The API token is kept in secure storage and survives app restarts and
 * updates, but the signed-in user was only ever held in the Laravel session —
 * which expires after SESSION_LIFETIME and is wiped when the app container is
 * replaced. check() then said "signed in" while user() returned null, so
 * players were bounced to the login screen with a perfectly valid token.
 */
function guardWithToken(array $me): QuestifyApiGuard
{
    session()->put('questify_api_token', 'valid-token');

    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('me')->andReturn(['data' => $me]);

    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('auth')->andReturn($auth);

    return new QuestifyApiGuard($client, session()->driver());
}

it('still holds the token after the session loses the user', function () {
    session()->put('questify_api_token', 'valid-token');

    expect(TokenStorage::has())->toBeTrue()
        ->and(session()->has('questify_user'))->toBeFalse();
});

it('restores the signed-in user from the token when the session has expired', function () {
    $guard = guardWithToken([
        'id' => 7,
        'name' => 'Kasper',
        'email' => 'kasper@example.com',
    ]);

    expect($guard->check())->toBeTrue()
        ->and($guard->user())->not->toBeNull()
        ->and($guard->user()->id)->toBe(7)
        ->and($guard->user()->name)->toBe('Kasper');
});

it('writes the restored user back to the session so the next request is cheap', function () {
    $guard = guardWithToken(['id' => 7, 'name' => 'Kasper']);

    $guard->user();

    expect(session()->get('questify_user.id'))->toBe(7);
});

it('answers id from the token-restored user', function () {
    $guard = guardWithToken(['id' => 7, 'name' => 'Kasper']);

    expect($guard->id())->toBe(7);
});

it('signs the player out when the stored token is no longer valid', function () {
    session()->put('questify_api_token', 'stale-token');

    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('me')->andThrow(new ApiAuthenticationException('Unauthenticated.'));

    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('auth')->andReturn($auth);

    $guard = new QuestifyApiGuard($client, session()->driver());

    expect($guard->user())->toBeNull()
        ->and(TokenStorage::has())->toBeFalse('A rejected token must be discarded, not retried forever.');
});

// --- Signing in and out ---

/**
 * A guard whose client answers the calls login() and logout() make. Anything
 * in $throws replaces the default behaviour for that method — Mockery honours
 * the first expectation it was given, so overrides must be set up first.
 *
 * @param  array<string, Throwable>  $throws
 */
function guardWithClient(array $throws = []): QuestifyApiGuard
{
    $auth = Mockery::mock(AuthResource::class);

    foreach ($throws as $method => $exception) {
        $auth->shouldReceive($method)->andThrow($exception);
    }

    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 7, 'name' => 'Kasper']]);
    $auth->shouldReceive('logout')->andReturnNull();
    $auth->shouldReceive('login')->andReturn([
        'data' => ['user' => ['id' => 7, 'name' => 'Kasper'], 'token' => 'fresh-token'],
    ]);

    $categories = Mockery::mock(CategoryApiResource::class);
    $categories->shouldReceive('list')->andReturn(['data' => []]);

    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('auth')->andReturn($auth);
    $client->shouldReceive('categories')->andReturn($categories);
    $client->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    return new QuestifyApiGuard($client, session()->driver());
}

it('stores the token and the user when someone signs in', function () {
    $guard = guardWithClient();

    $guard->login(['id' => 7, 'name' => 'Kasper'], 'fresh-token');

    expect($guard->user()->id)->toBe(7)
        ->and($guard->hasUser())->toBeTrue()
        ->and(TokenStorage::get())->toBe('fresh-token')
        ->and(session('questify_user.id'))->toBe(7);
});

it('remembers that this device has signed in before', function () {
    // The welcome screen sends a returning player straight to the login form
    // instead of the marketing pitch.
    guardWithClient()->login(['id' => 7, 'name' => 'Kasper'], 'fresh-token');

    expect(collect(cookie()->getQueuedCookies())->map->getName())
        ->toContain('has_logged_in');
});

it('signs in with an email and password', function () {
    $guard = guardWithClient();

    expect($guard->validate(['email' => 'k@example.com', 'password' => 'secret']))->toBeTrue()
        ->and($guard->user()->id)->toBe(7);
});

it('reports bad credentials as a failed sign-in rather than throwing', function () {
    $guard = guardWithClient(['login' => new ApiAuthenticationException('Invalid credentials.')]);

    expect($guard->validate(['email' => 'k@example.com', 'password' => 'wrong']))->toBeFalse();
});

it('survives a prefetch that fails so a slow backend cannot block signing in', function () {
    $guard = guardWithClient(['me' => new RuntimeException('API down')]);

    $guard->login(['id' => 7, 'name' => 'Kasper'], 'fresh-token');

    expect($guard->user()->id)->toBe(7);
});

it('clears the token and the user on sign out', function () {
    $guard = guardWithClient();
    $guard->login(['id' => 7, 'name' => 'Kasper'], 'fresh-token');

    $guard->logout();

    expect($guard->user())->toBeNull()
        ->and($guard->check())->toBeFalse()
        ->and(TokenStorage::has())->toBeFalse()
        ->and(session('questify_user'))->toBeNull();
});

it('signs out locally even when the backend cannot be reached', function () {
    // Otherwise a player on a dead connection stays signed in on the device.
    $guard = guardWithClient(['logout' => new RuntimeException('Offline')]);
    $guard->login(['id' => 7, 'name' => 'Kasper'], 'fresh-token');

    $guard->logout();

    expect(TokenStorage::has())->toBeFalse()
        ->and($guard->check())->toBeFalse();
});

it('is a guest until someone signs in', function () {
    $guard = guardWithClient();

    expect($guard->guest())->toBeTrue()
        ->and($guard->check())->toBeFalse()
        ->and($guard->hasUser())->toBeFalse();
});

it('accepts a user set directly on the guard', function () {
    $guard = guardWithClient();

    $guard->setUser(new ApiTokenUser(['id' => 9, 'name' => 'Anna']));

    expect($guard->user()->id)->toBe(9)
        ->and($guard->id())->toBe(9);
});

it('ignores a user of a kind it cannot represent', function () {
    // Livewire's actingAs can hand over an Eloquent user; the guard speaks
    // only API accounts and must not pretend otherwise.
    $guard = guardWithClient();

    $guard->setUser(User::factory()->create());

    expect($guard->user())->toBeNull();
});
