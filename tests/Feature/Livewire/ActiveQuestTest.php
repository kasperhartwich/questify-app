<?php

use App\Models\User;
use Livewire\Livewire;

/**
 * The navigation screen is where a player spends most of a quest, and it was
 * the largest untested surface in the app: proximity gating, stale participant
 * ids, the leaderboard and the realtime handlers all ran unverified.
 *
 * Nyhavn (checkpoint 1 in the fixture) sits at 55.6798, 12.5907 with a 50 m
 * arrival radius.
 */
beforeEach(fn () => mockFullApiClient());

function activeQuest(?User $as = null)
{
    return Livewire::actingAs($as ?? User::factory()->create())
        ->test('pages::session.active-quest', ['code' => 'XYZ789']);
}

it('loads the session and its checkpoints', function () {
    activeQuest()
        ->assertSet('code', 'XYZ789')
        ->assertCount('checkpoints', 2)
        ->assertSee('Nyhavn');
});

it('takes the arrival radius from the session, not a default', function () {
    activeQuest()->assertSet('arrivalRadius', 50);
});

it('recognises the signed-in player among the participants', function () {
    // Fixture participant 55 belongs to user 1, the first factory user.
    activeQuest()->assertSet('participantId', 55);
});

it('discards a participant id left over from another session', function () {
    // Answers submitted under a stale id would be credited to the wrong player.
    session()->put('questify_participant_id', 999);

    activeQuest()->assertSet('participantId', 55);

    expect(session('questify_participant_id'))->toBe(55);
});

// --- Proximity gate (spec §6) ---

it('reports the player as out of range when they are far away', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.7000, 12.6000)
        ->assertSet('withinRadius', false);
});

it('measures the distance to the current checkpoint in metres', function () {
    $component = activeQuest()->call('updatePlayerPosition', 55.7000, 12.6000);

    // Roughly 2.3 km from Nyhavn — the exact figure matters less than that a
    // real distance is computed rather than left null.
    expect($component->get('distanceMeters'))->toBeGreaterThan(2000)
        ->and($component->get('distanceMeters'))->toBeLessThan(3000);
});

it('reports the player as arrived once inside the radius', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.67980, 12.59070)
        ->assertSet('withinRadius', true)
        ->assertSet('distanceMeters', 0);
});

it('refuses to open the questions while the player is out of range', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.7000, 12.6000)
        ->call('goToQuestions')
        ->assertNoRedirect();
});

it('opens the questions once the player has arrived', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.67980, 12.59070)
        ->call('goToQuestions')
        ->assertRedirect('/session/XYZ789/question/1');
});

it('refuses to open the questions before any fix has arrived', function () {
    // withinRadius starts false: no fix must never read as "close enough".
    activeQuest()->call('goToQuestions')->assertNoRedirect();
});

it('remembers the last fix so the question screen can prove proximity', function () {
    activeQuest()->call('updatePlayerPosition', 55.67980, 12.59070);

    expect(session('questify_player_lat'))->toBe(55.67980)
        ->and(session('questify_player_lng'))->toBe(12.59070);
});

// --- Position events ---

it('tells the map where the player moved', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.68, 12.59)
        ->assertDispatched('player-moved');
});

it('warns the player when the fix is too rough to trust', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.68, 12.59, 120.0)
        ->assertDispatched('gps-weak');
});

it('stays quiet when the fix is accurate', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.68, 12.59, 8.0)
        ->assertNotDispatched('gps-weak');
});

it('ignores a successful native fix that carries no coordinates', function () {
    // Geolocation v2 can report success with nulls; the typed properties would
    // throw a TypeError if they were assigned.
    activeQuest()
        ->call('onLocationReceived', true, null, null)
        ->assertSet('playerLat', null)
        ->assertSet('withinRadius', false);
});

it('applies a native fix that does carry coordinates', function () {
    activeQuest()
        ->call('onLocationReceived', true, 55.67980, 12.59070)
        ->assertSet('withinRadius', true);
});

// --- Leaderboard ---

it('ranks the leaderboard and marks the player as themselves', function () {
    $component = activeQuest();

    expect($component->get('leaderboard'))->toBe([[
        'rank' => 1,
        'display_name' => 'Kasper Test',
        'score' => 100,
        'is_me' => true,
    ]]);
});

it('reloads the leaderboard when the server broadcasts a change', function () {
    activeQuest()->call('onLeaderboardUpdated')->assertSet('leaderboard.0.rank', 1);
});

it('sends the player to the results when the host ends the session', function () {
    activeQuest()->call('onSessionEnded')->assertRedirect('/session/XYZ789/complete');
});

// --- Hint ---

it('keeps the hint hidden until the player asks for it', function () {
    activeQuest()
        ->assertSet('showHint', false)
        ->call('showHint')
        ->assertSet('showHint', true);
});
