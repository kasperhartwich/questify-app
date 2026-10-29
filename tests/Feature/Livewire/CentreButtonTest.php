<?php

use App\Services\Api\Resources\UserApiResource;
use App\Services\TokenStorage;
use Livewire\Livewire;

/**
 * The middle button of the bottom bar asks to join a quest, or to continue the
 * one the player is already in.
 */
beforeEach(function () {
    mockFullApiClient();
    TokenStorage::set('test-token');
});

function playerSessions(array $participations): void
{
    $user = Mockery::mock(UserApiResource::class);
    $user->shouldReceive('sessions')->andReturn(['data' => $participations]);

    swapApiResource('user', $user);
}

function participation(string $status, array $overrides = []): array
{
    return array_merge([
        'current_checkpoint_index' => 1,
        'quest_completed_at' => null,
        'session' => [
            'session_code' => 'ABC123',
            'status' => $status,
            'quest' => ['title' => 'Copenhagen History Hunt', 'checkpoints_count' => 4],
        ],
    ], $overrides);
}

it('offers to join a quest when the player is in none', function () {
    playerSessions([]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'join')
        ->assertSet('href', '/join')
        ->assertSee('Join quest')
        ->assertDontSee('Continue');
});

it('offers to join a quest when the only sessions are finished or over', function () {
    playerSessions([
        participation('active', ['quest_completed_at' => '2026-10-01T10:00:00Z']),
        participation('completed'),
    ]);

    Livewire::test('centre-button')->assertSet('mode', 'join');
});

it('continues the quest in play, with its title and progress', function () {
    playerSessions([participation('completed'), participation('active')]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('href', route('session.play', 'ABC123', false))
        ->assertSet('title', 'Copenhagen History Hunt')
        ->assertSet('progress', 0.25)
        ->assertSee('Continue')
        ->assertSee('Copenhagen History Hunt')
        ->assertDontSee('Join quest');
});

it('continues to the lobby while the session is still waiting', function () {
    playerSessions([participation('waiting')]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('href', route('session.lobby', 'ABC123', false));
});

it('reads a paginated list of participations', function () {
    playerSessions(['data' => [participation('active')], 'next_cursor' => null]);

    Livewire::test('centre-button')->assertSet('mode', 'continue');
});

it('shows a plain ring when the quest size is unknown', function () {
    playerSessions([participation('active', ['session' => [
        'session_code' => 'ABC123',
        'status' => 'active',
        'quest' => ['title' => 'Copenhagen History Hunt'],
    ]])]);

    Livewire::test('centre-button')
        ->assertSet('mode', 'continue')
        ->assertSet('progress', null);
});

it('says Join quest and Continue in Danish', function () {
    app()->setLocale('da');
    playerSessions([]);

    Livewire::test('centre-button')->assertSee('Deltag i quest');

    playerSessions([participation('active')]);

    Livewire::test('centre-button')->assertSee('Fortsæt');
});

it('stays on join when the player is signed out', function () {
    TokenStorage::signOut();

    Livewire::test('centre-button')->assertSet('mode', 'join');
});
