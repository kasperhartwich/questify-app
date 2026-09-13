<?php

use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiAuthenticationException;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
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
