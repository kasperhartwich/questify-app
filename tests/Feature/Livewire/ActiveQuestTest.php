<?php

use App\Models\User;
use App\Services\Api\Resources\SessionApiResource;
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

// --- Play screen reveals nothing ahead (QU-23) ---

it('keeps the current checkpoint on the map', function () {
    $component = activeQuest();

    expect($component->get('checkpoints.0.latitude'))->not->toBeNull()
        ->and($component->get('checkpoints.0.longitude'))->not->toBeNull();
});

it('hides where the later checkpoints are', function () {
    $component = activeQuest();

    expect($component->get('checkpoints.1.latitude'))->toBeNull()
        ->and($component->get('checkpoints.1.longitude'))->toBeNull()
        ->and($component->html())->not->toContain('55.6841');
});

it('keeps the completed checkpoints on the map once the player has moved on', function () {
    session()->put('questify_checkpoint_index.XYZ789', 1);

    $component = activeQuest();

    expect($component->get('checkpoints.0.latitude'))->not->toBeNull()
        ->and($component->get('checkpoints.1.latitude'))->not->toBeNull();
});

function playSessionPayload(): array
{
    return ['data' => [
        'id' => 1, 'session_code' => 'XYZ789', 'status' => 'active', 'play_mode' => 'competitive_individual',
        'quest' => ['id' => 1, 'title' => 'Hunt', 'checkpoint_arrival_radius_meters' => 50],
        'participants' => [['id' => 55, 'user_id' => 1, 'display_name' => 'Kasper Test']],
        'checkpoints' => [['id' => 1, 'title' => 'Nyhavn', 'latitude' => '55.6798', 'longitude' => '12.5907']],
    ]];
}

it('asks the server for the checkpoints as the known participant', function () {
    session()->put('questify_participant_id', 55);
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->once()->with('XYZ789', 55)->andReturn(playSessionPayload());
    swapSessions($sessions);

    activeQuest()->assertSet('participantId', 55);
});

it('asks again as the participant once a signed-in player has been recognised', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('show')->once()->with('XYZ789', 0)->andReturn(playSessionPayload());
    $sessions->shouldReceive('show')->once()->with('XYZ789', 55)->andReturn(playSessionPayload());
    swapSessions($sessions);

    activeQuest()->assertSet('participantId', 55);
});

it('shows no leaderboard on the play screen', function () {
    activeQuest()
        ->assertDontSee(__('sessions.leaderboard'))
        ->assertDontSee('Kasper Test');
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

// --- Design ---

it('styles the checkpoint card like the quest detail cards, never dark', function () {
    activeQuest()
        ->assertSeeHtml('border-cream-border bg-white')
        ->assertDontSeeHtml('dark:bg-gray-800')
        ->assertDontSeeHtml('dark:bg-gray-700');
});

it('keeps the app cream-only by tying dark utilities to a class nothing sets', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('@custom-variant dark (&:where(.dark, .dark *));');
});

// --- Map auto-zoom (QU-37) ---

it('renders the map auto-zoom hooks and the recenter button', function () {
    activeQuest()
        ->assertSee('MapZoom.AutoZoom', false)
        ->assertSee('followPlayer', false)
        ->assertSee(__('sessions.recenter_map'));
});

it('forwards the GPS accuracy with each player move', function () {
    activeQuest()
        ->call('updatePlayerPosition', 55.7000, 12.6000, 12.5)
        ->assertDispatched('player-moved', latitude: 55.7, longitude: 12.6, accuracy: 12.5);
});
