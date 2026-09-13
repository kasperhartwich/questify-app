<?php

use App\Enums\ModerationStatus;
use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\ModerationFlag;
use App\Models\Quest;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Relationships and casts that the rest of the app takes for granted. A
 * mistyped foreign key here surfaces as a blank name or a missing score three
 * screens away, never as an error.
 */

// --- HasImageUrls ---

it('leaves an absolute image address alone', function () {
    // Covers already hosted elsewhere must not be prefixed with our storage path.
    $quest = new Quest;

    expect($quest->resolveImageUrl('https://cdn.example.com/cover.jpg'))
        ->toBe('https://cdn.example.com/cover.jpg')
        ->and($quest->resolveImageUrl('http://cdn.example.com/cover.jpg'))
        ->toBe('http://cdn.example.com/cover.jpg');
});

it('turns a stored path into a full address', function () {
    Storage::fake('public');

    expect((new Quest)->resolveImageUrl('quests/covers/abc.jpg'))
        ->toContain('quests/covers/abc.jpg');
});

it('has no address for a missing image', function () {
    expect((new Quest)->resolveImageUrl(null))->toBeNull();
});

it('resolves image addresses the same way on every model that shows one', function (string $class) {
    expect((new $class)->resolveImageUrl('https://example.com/x.png'))
        ->toBe('https://example.com/x.png');
})->with([Quest::class, User::class, Question::class]);

// --- CheckpointProgress ---

it('reads correctness as a boolean, not the database\'s one and zero', function () {
    $progress = CheckpointProgress::factory()->create(['is_correct' => 1]);

    expect($progress->fresh()->is_correct)->toBeTrue();
});

it('links a progress row back to the participant who earned it', function () {
    $participant = SessionParticipant::factory()->create();
    $progress = CheckpointProgress::factory()->create(['session_participant_id' => $participant->id]);

    expect($progress->sessionParticipant->id)->toBe($participant->id);
});

it('links a progress row to the checkpoint, question and answer it records', function () {
    $checkpoint = Checkpoint::factory()->create();
    $question = Question::factory()->create(['checkpoint_id' => $checkpoint->id]);
    $answer = Answer::factory()->create(['question_id' => $question->id]);

    $progress = CheckpointProgress::factory()->create([
        'checkpoint_id' => $checkpoint->id,
        'question_id' => $question->id,
        'answer_id' => $answer->id,
    ]);

    expect($progress->checkpoint->id)->toBe($checkpoint->id)
        ->and($progress->question->id)->toBe($question->id)
        ->and($progress->answer->id)->toBe($answer->id);
});

// --- ModerationFlag ---

it('lists only the reports still waiting on a moderator', function () {
    ModerationFlag::factory()->create(['status' => ModerationStatus::Pending]);
    ModerationFlag::factory()->create(['status' => ModerationStatus::Approved]);

    expect(ModerationFlag::pending()->count())->toBe(1);
});

it('reads a report\'s status as the enum, not a raw string', function () {
    $flag = ModerationFlag::factory()->create(['status' => ModerationStatus::Pending]);

    expect($flag->fresh()->status)->toBe(ModerationStatus::Pending);
});

it('keeps the reporter and the moderator apart', function () {
    // Both are user ids on the same row; swapping them would credit the wrong
    // person with the decision.
    $reporter = User::factory()->create(['name' => 'Reporter']);
    $moderator = User::factory()->create(['name' => 'Moderator']);

    $flag = ModerationFlag::factory()->create([
        'reporter_id' => $reporter->id,
        'moderator_id' => $moderator->id,
    ]);

    expect($flag->reporter->name)->toBe('Reporter')
        ->and($flag->moderator->name)->toBe('Moderator');
});

it('points a report back at whatever was reported', function () {
    $quest = Quest::factory()->create(['title' => 'Reported Quest']);
    $flag = ModerationFlag::factory()->create([
        'flaggable_type' => Quest::class,
        'flaggable_id' => $quest->id,
    ]);

    expect($flag->flaggable->title)->toBe('Reported Quest');
});

it('remembers when a report was resolved as a date', function () {
    $flag = ModerationFlag::factory()->create(['resolved_at' => '2026-09-13 10:00:00']);

    expect($flag->fresh()->resolved_at)->toBeInstanceOf(CarbonInterface::class);
});

// --- User ---

it('finds the quests a user created', function () {
    $user = User::factory()->create();
    Quest::factory()->create(['creator_id' => $user->id]);

    expect($user->quests)->toHaveCount(1);
});

it('finds the quests a user bookmarked', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create();
    $user->favouriteQuests()->attach($quest->id);

    expect($user->favouriteQuests->pluck('id')->all())->toBe([$quest->id]);
});

it('keeps hosted sessions apart from sessions joined', function () {
    $host = User::factory()->create();
    $session = QuestSession::factory()->create(['host_id' => $host->id]);

    $player = User::factory()->create();
    SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'user_id' => $player->id,
    ]);

    expect($host->hostedSessions)->toHaveCount(1)
        ->and($host->sessionParticipations)->toHaveCount(0)
        ->and($player->sessionParticipations)->toHaveCount(1)
        ->and($player->hostedSessions)->toHaveCount(0);
});

it('hides the password and remember token from serialisation', function () {
    // These end up in API payloads and logs if they are not hidden.
    $user = User::factory()->create()->toArray();

    expect($user)->not->toHaveKey('password')
        ->and($user)->not->toHaveKey('remember_token');
});
