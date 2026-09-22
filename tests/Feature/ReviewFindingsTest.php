<?php

use App\Auth\ApiTokenUser;
use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiConnectionException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Models\User;
use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\TokenStorage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\System;

/**
 * Regressions for the findings of an external code review (September 2026).
 * Each test names the failure a player would have seen.
 */

/**
 * A fresh mock of one API resource that forwards every call to the shared
 * fixture except the ones the test overrides. The fixtures are mocks already,
 * and Mockery cannot proxy a mock.
 *
 * @param  array<string, Closure>  $overrides
 */
function resourceWith(string $accessor, array $overrides): object
{
    $existing = app(QuestifyApiClient::class)->{$accessor}();
    $class = get_parent_class($existing) ?: get_class($existing);
    $mock = Mockery::mock($class);

    foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $name = $method->getName();

        if ($method->isConstructor() || $method->isStatic() || str_starts_with($name, '__')) {
            continue;
        }

        $mock->shouldReceive($name)->andReturnUsing(
            $overrides[$name] ?? fn (...$args) => $existing->{$name}(...$args)
        );
    }

    return $mock;
}

// --- Identity cache is scoped to the token ---

it('never answers one token with another account cached identity', function () {
    Http::fake([
        '*/auth/me' => fn (Request $request) => Http::response([
            'data' => ['id' => $request->hasHeader('Authorization', 'Bearer token-a') ? 1 : 2],
        ]),
    ]);

    $auth = app(QuestifyApiClient::class)->auth();

    TokenStorage::set('token-a');
    expect($auth->me()['data']['id'])->toBe(1);

    TokenStorage::set('token-b');
    expect($auth->me()['data']['id'])->toBe(2);
});

// --- Answers ---

it('submits the text answer "0" instead of dropping it', function () {
    Http::fake(['*/sessions/ABC123/answer' => Http::response(['data' => ['correct' => true]])]);

    app(QuestifyApiClient::class)->gameplay()->answer('ABC123', 5, 9, null, '0');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answer')
        && $request['answer_text'] === '0'
        && ! array_key_exists('answer_id', $request->data()));
});

// --- Dead connections ---

it('turns a dead connection into an api error the screens already handle', function () {
    Http::fake(['*/quests*' => fn () => throw new ConnectionException('Network is unreachable')]);

    expect(fn () => app(QuestifyApiClient::class)->get('/quests'))
        ->toThrow(ApiConnectionException::class);
});

it('says the connection dropped in words a player understands', function () {
    expect((new ApiConnectionException)->getMessage())->toBe(__('general.no_connection'))
        ->and(new ApiConnectionException)->toBeInstanceOf(ApiException::class);
});

// --- Rejected tokens are dropped everywhere ---

it('clears every copy of a rejected token on device', function () {
    System::shouldReceive('isMobile')->andReturn(true);
    $keychain = null;
    SecureStorage::shouldReceive('set')->andReturnUsing(function ($key, $value) use (&$keychain) {
        $keychain = $value;

        return true;
    });
    SecureStorage::shouldReceive('get')->andReturnUsing(function () use (&$keychain) {
        return $keychain;
    });
    SecureStorage::shouldReceive('delete')->andReturnUsing(function () use (&$keychain) {
        $keychain = null;

        return true;
    });

    TokenStorage::set('revoked');
    ApiCache::remember(AuthResource::meCacheKey(), fn () => ['data' => ['id' => 1]]);
    $cacheKey = AuthResource::meCacheKey();

    TokenStorage::signOut();

    expect(TokenStorage::get())->toBeNull()
        ->and(is_file(storage_path('app/private/api-token')))->toBeFalse()
        ->and(cache()->has('api:'.$cacheKey))->toBeFalse();
});

it('keeps the token out of the plain text session on device', function () {
    System::shouldReceive('isMobile')->andReturn(true);
    SecureStorage::shouldReceive('set')->andReturn(true);
    SecureStorage::shouldReceive('get')->andReturn('device-token');

    TokenStorage::set('device-token');

    expect(session()->has('questify_api_token'))->toBeFalse()
        ->and(TokenStorage::get())->toBe('device-token');
});

it('signs out fully when a favourite is refused for a revoked token', function () {
    mockFullApiClient();
    TokenStorage::set('revoked');

    swapApiResource('quests', resourceWith('quests', [
        'toggleFavourite' => fn () => throw new ApiAuthenticationException,
    ]));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-detail', ['quest' => 1])
        ->call('toggleFavourite')
        ->assertRedirect(route('login'));

    expect(TokenStorage::has())->toBeFalse();
});

// --- Failures do not pretend to succeed ---

it('keeps the rating form up when the rating is refused', function () {
    mockFullApiClient();

    swapApiResource('quests', resourceWith('quests', [
        'rate' => fn () => throw new ApiException(500, 'Server error.'),
    ]));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.quest-complete', ['code' => 'XYZ789'])
        ->set('ratingValue', 4)
        ->call('rateQuest')
        ->assertSet('hasRated', false)
        ->assertDispatched('api-error');
});

it('keeps the player signed in when deleting the account fails', function () {
    mockFullApiClient();

    swapApiResource('user', resourceWith('user', [
        'deleteAccount' => fn () => throw new ApiException(500, 'Server error.'),
    ]));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::profile.settings')
        ->call('deleteAccount')
        ->assertNoRedirect()
        ->assertDispatched('api-error');
});

it('keeps the host on the dashboard when ending the session fails', function () {
    mockFullApiClient();

    swapSessions(resourceWith('sessions', [
        'end' => fn () => throw new ApiException(500, 'Server error.'),
    ]));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.host-dashboard', ['code' => 'ABC123'])
        ->call('endSession')
        ->assertNoRedirect();
});

it('keeps the host in the lobby when starting fails', function () {
    mockFullApiClient();
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    swapSessions(resourceWith('sessions', [
        'start' => fn () => throw new ApiException(500, 'Server error.'),
    ]));

    Livewire::actingAs(new ApiTokenUser(['id' => 2, 'name' => 'Bent Hansen', 'locale' => 'en']))
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->call('startSession')
        ->assertNoRedirect();
});

// --- Private quests: the API checks the access code ---

/**
 * @param  array<string, Closure>  $sessionOverrides
 */
function privateQuestDetail(array $sessionOverrides = [])
{
    mockFullApiClient();

    $detail = app(QuestifyApiClient::class)->quests()->show(1);
    $detail['data']['visibility'] = 'private';

    swapApiResource('quests', resourceWith('quests', ['show' => fn () => $detail]));
    swapSessions(resourceWith('sessions', $sessionOverrides));

    return Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-detail', ['quest' => 1]);
}

it('unlocks the start button for any code typed and leaves the check to the api', function () {
    // The API never sends the code, so comparing locally rejected every right one.
    privateQuestDetail()
        ->set('accessCode', 'SPARK1')
        ->call('verifyAccessCode')
        ->assertSet('accessGranted', true)
        ->assertHasNoErrors();
});

it('asks for a code before unlocking a private quest', function () {
    privateQuestDetail()
        ->set('accessCode', '  ')
        ->call('verifyAccessCode')
        ->assertSet('accessGranted', false)
        ->assertHasErrors('accessCode');
});

it('sends the typed code when starting a private quest', function () {
    $sent = null;

    privateQuestDetail([
        'create' => function ($questId, $mode, $code = null) use (&$sent) {
            $sent = $code;

            return ['data' => ['session_code' => 'XYZ789']];
        },
    ])
        ->set('accessCode', ' spark1 ')
        ->call('verifyAccessCode')
        ->set('playMode', 'competitive_individual')
        ->call('startQuest');

    expect($sent)->toBe('spark1');
});

it('shows the code as wrong when the api refuses it', function () {
    privateQuestDetail([
        'create' => fn () => throw new ApiValidationException('Invalid.', ['access_code' => ['Wrong.']]),
    ])
        ->set('accessCode', 'WRONG1')
        ->call('verifyAccessCode')
        ->call('startQuest')
        ->assertSet('accessGranted', false)
        ->assertHasErrors('accessCode')
        ->assertNoRedirect();
});

// --- Guests who joined can play ---

it('lets a guest who joined into the lobby', function () {
    mockFullApiClient();

    $this->withSession(['questify_participant_id' => 55])
        ->get('/session/ABC123')
        ->assertSuccessful();
});

it('sends someone with neither an account nor a join back to the start', function () {
    $this->get('/session/ABC123/play')->assertRedirect('/');
});

it('still requires an account to host', function () {
    $this->withSession(['questify_participant_id' => 55])
        ->get('/session/ABC123/host')
        ->assertRedirect();
});

// --- Checkpoint progress belongs to one session ---

it('does not carry a finished quest position into the next session', function () {
    mockFullApiClient();
    session()->put('questify_checkpoint_index.OLD111', 3);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.active-quest', ['code' => 'ABC123'])
        ->assertSet('currentCheckpointIndex', 0);
});
