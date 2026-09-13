<?php

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\SessionApiResource;
use App\Services\Api\Resources\UserApiResource;
use Illuminate\Support\Facades\Cache;

/**
 * These wrappers are mocked out of every page test, so nothing checked the
 * addresses and payloads they actually build. A wrong path or a dropped field
 * only shows up against the real backend.
 */
function stubClient(): QuestifyApiClient
{
    return Mockery::mock(QuestifyApiClient::class);
}

// --- AuthResource ---

it('signs in with an email and password', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()
        ->with('/auth/login', ['email' => 'k@example.com', 'password' => 'secret'])
        ->andReturn(['data' => []]);

    (new AuthResource($client))->login('k@example.com', 'secret');
});

it('registers an account', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/auth/register', [
        'name' => 'Kasper',
        'email' => 'k@example.com',
        'password' => 'secret',
        'password_confirmation' => 'secret',
    ])->andReturn(['data' => []]);

    (new AuthResource($client))->register('Kasper', 'k@example.com', 'secret', 'secret');
});

it('includes a phone number in a registration when one was given', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->withArgs(
        fn ($url, $data) => $data['phone_number'] === '+4512345678'
    )->andReturn(['data' => []]);

    (new AuthResource($client))->register('Kasper', 'k@example.com', 'secret', 'secret', '+4512345678');
});

it('builds every auth address the app uses', function (string $method, array $args, string $url) {
    $client = stubClient();
    $client->shouldReceive('post')->once()->withArgs(fn ($u) => $u === $url)->andReturn(['data' => []]);

    (new AuthResource($client))->{$method}(...$args);
})->with([
    'one time code' => ['verifyOtp', ['123456', 'a-token'], '/auth/verify-otp'],
    'submit phone' => ['submitPhone', ['+4512345678'], '/auth/submit-phone'],
    'verify phone' => ['verifyPhone', ['123456'], '/auth/verify-phone'],
    'resend verification' => ['resendVerification', [], '/auth/resend-verification'],
    'sign in by phone' => ['loginPhone', ['+4512345678'], '/auth/login/phone'],
    'register by phone' => ['registerPhone', ['+4512345678'], '/auth/register/phone'],
    'forgot password' => ['forgotPassword', ['k@example.com'], '/auth/forgot-password'],
    'test account' => ['tester', [], '/auth/tester'],
]);

it('sends both password fields on a reset', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/auth/reset-password', [
        'token' => 'reset-token',
        'email' => 'k@example.com',
        'password' => 'new-secret',
        'password_confirmation' => 'new-secret',
    ])->andReturn(['data' => []]);

    (new AuthResource($client))->resetPassword('reset-token', 'k@example.com', 'new-secret', 'new-secret');
});

it('signs out', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/auth/logout')->andReturn([]);

    (new AuthResource($client))->logout();
});

it('unlinks a connected account by provider', function () {
    $client = stubClient();
    $client->shouldReceive('delete')->once()->with('/auth/social/apple')->andReturn(['data' => []]);

    (new AuthResource($client))->unlinkSocial('apple');
});

it('caches the signed-in account rather than refetching it on every screen', function () {
    $client = stubClient();
    $client->shouldReceive('get')->once()->with('/auth/me')->andReturn(['data' => ['id' => 1]]);

    $auth = new AuthResource($client);
    $auth->me();
    $auth->me();
});

// --- SessionApiResource ---

it('starts a session for a quest in a chosen mode', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()
        ->with('/sessions', ['quest_id' => 1, 'play_mode' => 'competitive_teams'])
        ->andReturn(['data' => []]);

    (new SessionApiResource($client))->create(1, 'competitive_teams');
});

it('reads a session by its join code', function () {
    $client = stubClient();
    $client->shouldReceive('get')->once()->with('/sessions/ABC123')->andReturn(['data' => []]);

    (new SessionApiResource($client))->show('ABC123');
});

it('joins a session as a guest without a user id', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()
        ->with('/sessions/ABC123/join', ['display_name' => 'Kasper'])
        ->andReturn(['data' => []]);

    (new SessionApiResource($client))->join('ABC123', 'Kasper');
});

it('joins a session as a signed-in player', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()
        ->with('/sessions/ABC123/join', ['display_name' => 'Kasper', 'user_id' => 7])
        ->andReturn(['data' => []]);

    (new SessionApiResource($client))->join('ABC123', 'Kasper', 7);
});

it('builds the host controls for a session', function (string $method, string $url) {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with($url)->andReturn(['data' => []]);

    (new SessionApiResource($client))->{$method}('ABC123');
})->with([
    'start' => ['start', '/sessions/ABC123/start'],
    'end' => ['end', '/sessions/ABC123/end'],
]);

it('reads the host dashboard', function () {
    $client = stubClient();
    $client->shouldReceive('get')->once()->with('/sessions/ABC123/dashboard')->andReturn(['data' => []]);

    (new SessionApiResource($client))->dashboard('ABC123');
});

it('refreshes my sessions whenever one is created, started or ended', function (string $method, array $args) {
    // My Quests reads user:sessions; leaving it cached showed a finished
    // session as still in progress.
    $client = stubClient();
    $client->shouldReceive('post')->andReturn(['data' => []]);
    $client->shouldReceive('get')->twice()->with('/user/sessions')->andReturn(['data' => []]);

    $userResource = new UserApiResource($client);
    $userResource->sessions();

    (new SessionApiResource($client))->{$method}(...$args);

    $userResource->sessions();
})->with([
    'created' => ['create', [1, 'solo']],
    'started' => ['start', ['ABC123']],
    'ended' => ['end', ['ABC123']],
]);

// --- GameplayApiResource ---

it('proves where the player is standing on arrival', function () {
    // The server re-checks proximity, so the fix has to travel with the call.
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/sessions/ABC123/arrived', [
        'participant_id' => 55,
        'checkpoint_id' => 1,
        'latitude' => 55.6798,
        'longitude' => 12.5907,
    ])->andReturn(['data' => []]);

    (new GameplayApiResource($client))->arrived('ABC123', 55, 1, 55.6798, 12.5907);
});

it('submits a chosen answer by id', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/sessions/ABC123/answer', [
        'participant_id' => 55,
        'question_id' => 10,
        'answer_id' => 2,
    ])->andReturn(['data' => []]);

    (new GameplayApiResource($client))->answer('ABC123', 55, 10, 2);
});

it('submits a typed answer as text', function () {
    $client = stubClient();
    $client->shouldReceive('post')->once()->with('/sessions/ABC123/answer', [
        'participant_id' => 55,
        'question_id' => 11,
        'answer_text' => 'Anno 1743',
    ])->andReturn(['data' => []]);

    (new GameplayApiResource($client))->answer('ABC123', 55, 11, null, 'Anno 1743');
});

it('reads the leaderboard for a session', function () {
    $client = stubClient();
    $client->shouldReceive('get')->once()->with('/sessions/ABC123/leaderboard')->andReturn(['data' => []]);

    (new GameplayApiResource($client))->leaderboard('ABC123');
});

// --- CategoryApiResource ---

it('caches the category list, which almost never changes', function () {
    $client = stubClient();
    $client->shouldReceive('get')->once()->with('/categories')->andReturn(['data' => []]);

    $resource = new CategoryApiResource($client);
    $resource->list();
    $resource->list();
});

afterEach(function () {
    ApiCache::flush();
    Cache::forget('api_categories');
});
