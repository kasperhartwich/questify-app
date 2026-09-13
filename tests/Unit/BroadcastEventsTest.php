<?php

use App\Events\CheckpointArrived;
use App\Events\CheckpointCompleted;
use App\Events\LeaderboardUpdated;
use App\Events\ParticipantJoined;
use App\Events\QuestCompleted;
use App\Events\SessionEnded;
use App\Events\SessionStarted;
use App\Models\Checkpoint;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Illuminate\Broadcasting\PresenceChannel;

/**
 * Realtime fails silently: a channel name that does not match what the screens
 * subscribe to produces no error, just a dashboard that never updates. The
 * screens listen on "session.{code}", so every event must broadcast there and
 * carry the fields the listener reads.
 */
function channelNames(object $event): array
{
    return array_map(fn (PresenceChannel $c) => $c->name, $event->broadcastOn());
}

it('broadcasts on the session channel the screens subscribe to', function (string $class) {
    $session = QuestSession::factory()->create([
        'join_code' => 'ABC123',
        'started_at' => now(),
        'completed_at' => now(),
    ]);
    $participant = SessionParticipant::factory()->create(['quest_session_id' => $session->id]);
    $checkpoint = Checkpoint::factory()->create();

    $event = match ($class) {
        SessionStarted::class, SessionEnded::class => new $class($session),
        ParticipantJoined::class => new $class('ABC123', $participant),
        QuestCompleted::class => new $class('ABC123', $participant, 300),
        LeaderboardUpdated::class => new $class('ABC123', collect()),
        default => new $class('ABC123', $participant, $checkpoint),
    };

    expect(channelNames($event))->toBe(['presence-session.ABC123']);
})->with([
    CheckpointArrived::class,
    CheckpointCompleted::class,
    ParticipantJoined::class,
    QuestCompleted::class,
    LeaderboardUpdated::class,
    SessionStarted::class,
    SessionEnded::class,
]);

it('tells the host who arrived where', function () {
    $participant = SessionParticipant::factory()->create(['display_name' => 'Kasper']);
    $checkpoint = Checkpoint::factory()->create(['title' => 'Nyhavn']);

    expect((new CheckpointArrived('ABC123', $participant, $checkpoint))->broadcastWith())->toBe([
        'participant_id' => $participant->id,
        'display_name' => 'Kasper',
        'checkpoint_id' => $checkpoint->id,
        'checkpoint_title' => 'Nyhavn',
    ]);
});

it('tells the host who finished a checkpoint', function () {
    $participant = SessionParticipant::factory()->create(['display_name' => 'Kasper']);
    $checkpoint = Checkpoint::factory()->create(['title' => 'Nyhavn']);

    $payload = (new CheckpointCompleted('ABC123', $participant, $checkpoint))->broadcastWith();

    expect($payload['display_name'])->toBe('Kasper')
        ->and($payload['checkpoint_title'])->toBe('Nyhavn');
});

it('carries the new player so the lobby can list them without refetching', function () {
    $participant = SessionParticipant::factory()->create(['display_name' => 'Anna']);

    $payload = (new ParticipantJoined('ABC123', $participant))->broadcastWith();

    expect($payload['participant']['display_name'])->toBe('Anna')
        ->and($payload['participant']['id'])->toBe($participant->id);
});

it('carries the final score when someone finishes', function () {
    $participant = SessionParticipant::factory()->create(['display_name' => 'Kasper']);

    expect((new QuestCompleted('ABC123', $participant, 420))->broadcastWith())->toBe([
        'participant_id' => $participant->id,
        'display_name' => 'Kasper',
        'final_score' => 420,
    ]);
});

it('carries the whole standings so every screen updates at once', function () {
    $leaderboard = collect([
        ['participant_id' => 55, 'display_name' => 'Kasper', 'score' => 300, 'rank' => 1],
    ]);

    expect((new LeaderboardUpdated('ABC123', $leaderboard))->broadcastWith())
        ->toBe(['leaderboard' => $leaderboard->toArray()]);
});

it('timestamps the start of a session', function () {
    $session = QuestSession::factory()->create(['join_code' => 'ABC123', 'started_at' => now()]);

    $payload = (new SessionStarted($session))->broadcastWith();

    expect($payload['session_id'])->toBe($session->id)
        ->and($payload['started_at'])->toBe($session->started_at->toIso8601String());
});

it('timestamps the end of a session', function () {
    $session = QuestSession::factory()->create(['join_code' => 'ABC123', 'completed_at' => now()]);

    $payload = (new SessionEnded($session))->broadcastWith();

    expect($payload['completed_at'])->toBe($session->completed_at->toIso8601String());
});
