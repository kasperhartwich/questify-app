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

it('sends the player home with a plain message when the session ends', function (string $screen, array $params) {
    Livewire::actingAs(User::factory()->create())
        ->test($screen, $params)
        ->call('onSessionEnded')
        ->assertRedirect('/');

    expect(session('session_ended'))->toBe(__('sessions.ended_under_you'))
        ->and(session()->has('questify_participant_id'))->toBeFalse();
})->with([
    'lobby' => ['pages::session.lobby', ['code' => 'ABC123']],
    'host dashboard' => ['pages::session.host-dashboard', ['code' => 'ABC123']],
    'question screen' => ['pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1]],
]);

it('maps a not-active answer to the same message and home', function () {
    $gameplay = Mockery::mock(GameplayApiResource::class);
    $gameplay->shouldReceive('arrived')->andThrow(notActive());
    $gameplay->shouldReceive('leaderboard')->andReturn(['data' => []]);
    swapGameplayResource($gameplay);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.question-screen', ['code' => 'XYZ789', 'checkpoint' => 1])
        ->assertRedirect('/');

    expect(session('session_ended'))->toBe(__('sessions.ended_under_you'));
});

it('maps a missing session on the lobby to the message instead of a Not Found page', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->andThrow(new ApiNotFoundException);

    swapSessions($sessions);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->assertRedirect('/');

    expect(session('session_ended'))->not->toBeNull();
});

it('maps not-active when the host ends from the dashboard', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('dashboard')->andReturn(app(QuestifyApiClient::class)->sessions()->dashboard('ABC123'));
    $sessions->shouldReceive('end')->andThrow(notActive());
    swapSessions($sessions);

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
