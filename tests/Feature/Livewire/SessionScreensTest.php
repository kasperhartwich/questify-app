<?php

use App\Auth\ApiTokenUser;
use App\Models\User;
use App\Services\Api\Resources\SessionApiResource;
use Livewire\Livewire;

/**
 * The lobby, the host dashboard and the results screen were all untested. They
 * carry the host-only controls and the realtime handlers, so a regression here
 * is invisible until a real group is standing in the street.
 */
beforeEach(fn () => mockFullApiClient());

// --- Lobby ---

function lobby(string $code = 'ABC123', ?User $as = null)
{
    return Livewire::actingAs($as ?? User::factory()->create())
        ->test('pages::session.lobby', ['code' => $code]);
}

it('shows the quest a lobby is waiting to play', function () {
    lobby()
        ->assertSet('code', 'ABC123')
        ->assertSee('Copenhagen History Hunt');
});

it('offers a join address other players can use', function () {
    lobby()->assertSet('joinUrl', url('/join/ABC123/name'));
});

it('treats a guest as not the host', function () {
    // The fixture host is user 2; a fresh factory user is someone else.
    lobby()->assertSet('isHost', false);
});

it('refuses to start the session for anyone but the host', function () {
    lobby()->call('startSession')->assertNoRedirect();
});

it('lets the host start the session and takes them to the dashboard', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    Livewire::actingAs(new ApiTokenUser(['id' => 2, 'name' => 'Bent Hansen', 'locale' => 'en']))
        ->test('pages::session.lobby', ['code' => 'ABC123'])
        ->assertSet('isHost', true)
        ->call('startSession')
        ->assertRedirect('/session/ABC123/host');
});

it('moves everyone into play when the host starts', function () {
    lobby()->call('onSessionStarted')->assertRedirect('/session/ABC123/play');
});

it('refreshes the roster when someone joins', function () {
    lobby()->call('onParticipantJoined')->assertSet('code', 'ABC123');
});

it('hands the join address to the share sheet', function () {
    lobby()->call('shareSession')->assertDispatched('share-session');
});

// --- Host dashboard ---

function hostDashboard()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::session.host-dashboard', ['code' => 'ABC123']);
}

it('loads the dashboard for a session', function () {
    hostDashboard()->assertSet('code', 'ABC123');
});

it('summarises each participant\'s progress', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('dashboard')->andReturn(['data' => [
        'session' => ['id' => 1, 'session_code' => 'ABC123', 'status' => 'active', 'quest' => ['title' => 'Copenhagen History Hunt', 'checkpoint_count' => 3]],
        'participants' => [
            ['id' => 55, 'display_name' => 'Kasper', 'total_score' => 300, 'current_checkpoint_index' => 3, 'quest_completed_at' => '2026-09-13T10:00:00Z'],
            ['id' => 56, 'display_name' => 'Anna', 'total_score' => 100, 'current_checkpoint_index' => 1, 'quest_completed_at' => null],
        ],
    ]]);
    $sessions->shouldReceive('end')->andReturn(['data' => []]);
    swapSessions($sessions);

    $component = hostDashboard();

    expect($component->get('participants'))->toBe([
        ['id' => 55, 'display_name' => 'Kasper', 'score' => 300, 'checkpoints_completed' => 3, 'total_checkpoints' => 3, 'status' => 'finished'],
        ['id' => 56, 'display_name' => 'Anna', 'score' => 100, 'checkpoints_completed' => 1, 'total_checkpoints' => 3, 'status' => 'playing'],
    ]);
});

it('takes the host to the results after ending the session', function () {
    $sessions = Mockery::mock(SessionApiResource::class);
    $sessions->shouldReceive('dashboard')->andReturn(['data' => ['session' => [], 'participants' => []]]);
    $sessions->shouldReceive('end')->once()->andReturn(['data' => []]);
    swapSessions($sessions);

    hostDashboard()->call('endSession')->assertRedirect('/session/ABC123/complete');
});

it('reloads the dashboard on every realtime event the host cares about', function (string $handler) {
    hostDashboard()->call($handler)->assertSet('code', 'ABC123');
})->with([
    'a checkpoint was completed' => 'onCheckpointCompleted',
    'the leaderboard moved' => 'onLeaderboardUpdated',
    'someone finished' => 'onQuestCompleted',
    'someone joined' => 'onParticipantJoined',
]);

// --- Results ---

function questComplete()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::session.quest-complete', ['code' => 'XYZ789']);
}

it('names the quest that was just played', function () {
    questComplete()
        ->assertSet('questId', 1)
        ->assertSet('questTitle', 'Copenhagen History Hunt');
});

it('picks the player out of the final standings', function () {
    session()->put('questify_participant_id', 55);

    questComplete()
        ->assertSet('myScore', 100)
        ->assertSet('myRank', 1);
});

it('leaves the score at zero for someone who only watched', function () {
    session()->put('questify_participant_id', 0);

    questComplete()
        ->assertSet('myScore', 0)
        ->assertSet('myRank', 0);
});

it('records a rating between one and five', function () {
    questComplete()
        ->set('ratingValue', 4)
        ->call('rateQuest')
        ->assertSet('hasRated', true);
});

it('refuses a rating outside the scale', function (int $value) {
    questComplete()
        ->set('ratingValue', $value)
        ->call('rateQuest')
        ->assertSet('hasRated', false);
})->with(['nothing chosen' => 0, 'below the scale' => -1, 'above the scale' => 6]);

it('refuses to rate the same quest twice', function () {
    $component = questComplete()
        ->set('ratingValue', 5)
        ->call('rateQuest')
        ->set('ratingValue', 1)
        ->call('rateQuest');

    // The second call returned early, so the first rating stands.
    expect($component->get('hasRated'))->toBeTrue();
});

it('hands the result to the share sheet', function () {
    questComplete()->call('shareResult')->assertDispatched('share-result');
});
