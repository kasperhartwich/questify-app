<?php

use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiValidationException;
use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\GameplayApiResource;
use App\Services\Api\Resources\SessionApiResource;
use Livewire\Livewire;

/**
 * A session can end under the player (the host ended it, or it sat idle past the
 * backend timeout). Every session screen sends them home with a plain message.
 */
beforeEach(function () {
    mockFullApiClient();
    session()->put('questify_participant_id', 55);
    session()->put('questify_player_lat', 55.67980);
    session()->put('questify_player_lng', 12.59070);
});

function notActive(): ApiValidationException
{
    return new ApiValidationException('Session is not active.', [], ['message' => 'Session is not active.']);
}

/**
 * The sessions resource with show() answering the given status (or a 404 when
 * null) and any further calls overridden.
 *
 * @param  array<string, Closure>  $overrides
 */
function sessionsShowing(?string $status, array $overrides = []): SessionApiResource
{
    $fixture = app(QuestifyApiClient::class)->sessions();
    $mock = Mockery::mock(SessionApiResource::class);

    $mock->shouldReceive('show')->andReturnUsing(
        $status === null
            ? fn () => throw new ApiNotFoundException
            : fn () => ['data' => ['status' => $status]],
    );

    foreach (['start', 'end', 'leave', 'dashboard', 'join', 'create'] as $call) {
        $mock->shouldReceive($call)->andReturnUsing($overrides[$call] ?? fn (...$args) => $fixture->{$call}(...$args));
    }

    return $mock;
}

it('sends the lobby home with a plain message when the session ends', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->call('onSessionEnded')
        ->assertRedirect('/');

    expect(session('session_ended'))->toBe(__('sessions.ended_under_you'))
        ->and(session()->has('questify_participant_id'))->toBeFalse();
});

it('takes the host and the players on a question to the results when the session ends', function (string $screen, array $params) {
    Livewire::actingAs(User::factory()->create())
        ->test($screen, $params)
        ->call('onSessionEnded')
        ->assertRedirect('/session/'.$params['code'].'/complete');

    expect(session()->has('session_ended'))->toBeFalse();
})->with([
    'host dashboard' => ['pages::session.host-dashboard', ['code' => 'ABC123']],
    'question screen' => ['pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1]],
]);

it('maps a not-active answer to the message and home once the session is confirmed over', function () {
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('arrived')->andThrow(notActive());
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    swapGameplayResource($gameplay);
    swapSessions(sessionsShowing('completed'));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1])
        ->assertRedirect('/');

    expect(session('session_ended'))->toBe(__('sessions.ended_under_you'));
});

it('keeps a too-far answer on the navigation path while the session is still active', function () {
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('arrived')->andThrow(new ApiValidationException('You are too far from the checkpoint.', []));
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    swapGameplayResource($gameplay);
    swapSessions(sessionsShowing('active'));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1])
        ->assertRedirect('/session/XYZ789/play');

    expect(session()->has('session_ended'))->toBeFalse()
        ->and(session('questify_participant_id'))->toBe(55);
});

it('maps a missing session to the message instead of a Not Found page', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->andThrow(new ApiNotFoundException);
    swapSessions($sessions);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->assertRedirect('/');

    expect(session('session_ended'))->not->toBeNull();
});

it('still shows a Not Found page for a missing checkpoint in a live session', function () {
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('arrived')->andThrow(new ApiNotFoundException);
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    swapGameplayResource($gameplay);
    swapSessions(sessionsShowing('active'));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 9])
        ->assertNotFound();

    expect(session()->has('session_ended'))->toBeFalse();
});

it('maps not-active when the host ends from a session that is over', function () {
    swapSessions(sessionsShowing('completed', ['end' => fn () => throw notActive()]));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.host-dashboard', ['code' => 'ABC123'])
        ->call('endSession')
        ->assertRedirect('/');
});

it('keeps a validation error with fields on a session screen', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->andReturn(app(QuestifyApiClient::class)->sessions()->show('ABC123'));
    $sessions->shouldReceive('start')->andThrow(new ApiValidationException('Invalid.', ['code' => ['Bad code']]));
    swapSessions($sessions);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->call('startSession')
        ->assertNoRedirect();
});

it('shows the message as a dialog on the next screen', function () {
    session()->flash('session_ended', __('sessions.ended_under_you'));

    Livewire::test('dialog')
        ->assertSet('show', true)
        ->assertSet('title', __('sessions.ended_title'))
        ->assertSet('message', __('sessions.ended_under_you'));
});

it('keeps the dialog closed when nothing ended', function () {
    Livewire::test('dialog')->assertSet('show', false);
});

it('has the message in both languages', function () {
    foreach (['en', 'da'] as $locale) {
        expect(__('sessions.ended_under_you', [], $locale))->not->toBe('sessions.ended_under_you')
            ->and(__('sessions.ended_title', [], $locale))->not->toBe('sessions.ended_title');
    }
});
