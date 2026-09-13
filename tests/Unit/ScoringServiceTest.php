<?php

use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Services\ScoringService;

beforeEach(function () {
    $this->service = new ScoringService;
});

// --- calculateCorrectAnswerScore ---

it('returns base points for correct answer', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => 100, 'scoring_speed_bonus_enabled' => false]);
    $progress = new CheckpointProgress(['time_taken_seconds' => 5]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['base'])->toBe(100);
    expect($result['speed_bonus'])->toBe(0);
    expect($result['total'])->toBe(100);
});

it('gives speed bonus when enabled', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => 100, 'scoring_speed_bonus_enabled' => true]);
    $progress = new CheckpointProgress(['time_taken_seconds' => 0]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['base'])->toBe(100);
    expect($result['speed_bonus'])->toBe(50);
    expect($result['total'])->toBe(150);
});

it('gives half speed bonus at 15 seconds', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => 100, 'scoring_speed_bonus_enabled' => true]);
    $progress = new CheckpointProgress(['time_taken_seconds' => 15]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['speed_bonus'])->toBe(25);
    expect($result['total'])->toBe(125);
});

it('gives no speed bonus at 30 seconds', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => 100, 'scoring_speed_bonus_enabled' => true]);
    $progress = new CheckpointProgress(['time_taken_seconds' => 30]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['speed_bonus'])->toBe(0);
    expect($result['total'])->toBe(100);
});

it('gives no speed bonus without time_taken_seconds', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => 100, 'scoring_speed_bonus_enabled' => true]);
    $progress = new CheckpointProgress(['time_taken_seconds' => null]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['speed_bonus'])->toBe(0);
    expect($result['total'])->toBe(100);
});

it('uses default 100 points when scoring_points_per_correct is null', function () {
    $quest = Quest::factory()->create(['scoring_points_per_correct' => null, 'scoring_speed_bonus_enabled' => false]);
    $progress = new CheckpointProgress(['time_taken_seconds' => 5]);

    $result = $this->service->calculateCorrectAnswerScore($quest, $progress);

    expect($result['base'])->toBe(100);
    expect($result['total'])->toBe(100);
});

// --- calculateCompletionBonus ---

it('returns 0 if completion bonus disabled', function () {
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => false,
        'estimated_duration_minutes' => 60,
    ]);
    $session = QuestSession::factory()->create(['started_at' => now()]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => now(),
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(0);
});

it('returns 0 if session not started', function () {
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => true,
        'estimated_duration_minutes' => 60,
    ]);
    $session = QuestSession::factory()->create(['started_at' => null]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => now(),
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(0);
});

it('returns 0 if participant not finished', function () {
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => true,
        'estimated_duration_minutes' => 60,
    ]);
    $session = QuestSession::factory()->create(['started_at' => now()]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => null,
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(0);
});

it('gives max completion bonus for instant finish', function () {
    $startedAt = now();
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => true,
        'estimated_duration_minutes' => 1,
    ]);
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt,
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(200);
});

it('gives reduced completion bonus based on time', function () {
    $startedAt = now();
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => true,
        'estimated_duration_minutes' => 1,
    ]);
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt->copy()->addSeconds(30),
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(100);
});

it('gives 0 completion bonus when time exceeds estimate', function () {
    $startedAt = now();
    $quest = Quest::factory()->create([
        'scoring_quest_completion_time_bonus_enabled' => true,
        'estimated_duration_minutes' => 1,
    ]);
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt->copy()->addSeconds(120),
    ]);

    expect($this->service->calculateCompletionBonus($quest, $participant, $session))->toBe(0);
});

// --- applyWrongAnswerPenalty ---

it('deducts nothing when the quest does not penalise wrong answers', function () {
    $quest = Quest::factory()->create([
        'scoring_wrong_attempt_penalty_enabled' => false,
        'wrong_answer_penalty_points' => 25,
    ]);
    $progress = CheckpointProgress::factory()->create(['points_earned' => 100]);

    expect($this->service->applyWrongAnswerPenalty($quest, $progress))->toBe(0)
        ->and($progress->fresh()->points_earned)->toBe(100);
});

it('deducts the configured penalty', function () {
    $quest = Quest::factory()->create([
        'scoring_wrong_attempt_penalty_enabled' => true,
        'wrong_answer_penalty_points' => 25,
    ]);
    $progress = CheckpointProgress::factory()->create(['points_earned' => 100]);

    expect($this->service->applyWrongAnswerPenalty($quest, $progress))->toBe(25)
        ->and($progress->fresh()->points_earned)->toBe(75);
});

it('never drives a score below zero', function () {
    // Reporting a 40-point deduction from a 10-point score would show the
    // player losing points they never had.
    $quest = Quest::factory()->create([
        'scoring_wrong_attempt_penalty_enabled' => true,
        'wrong_answer_penalty_points' => 40,
    ]);
    $progress = CheckpointProgress::factory()->create(['points_earned' => 10]);

    expect($this->service->applyWrongAnswerPenalty($quest, $progress))->toBe(10)
        ->and($progress->fresh()->points_earned)->toBe(0);
});

it('treats a missing penalty amount as no penalty', function () {
    $quest = Quest::factory()->create([
        'scoring_wrong_attempt_penalty_enabled' => true,
        'wrong_answer_penalty_points' => null,
    ]);
    $progress = CheckpointProgress::factory()->create(['points_earned' => 100]);

    expect($this->service->applyWrongAnswerPenalty($quest, $progress))->toBe(0);
});

// --- handleWrongAnswer (spec 5.15) ---

function wrongAnswerSetup(string $behaviour, array $questAttributes = []): array
{
    $quest = Quest::factory()->create(array_merge([
        'wrong_answer_behaviour' => $behaviour,
    ], $questAttributes));

    $checkpoint = Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'hint' => 'Look above the door',
    ]);

    $progress = CheckpointProgress::factory()->create(['wrong_attempts' => 0]);

    return [$quest, $progress, $checkpoint];
}

it('counts every wrong attempt', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('retry_free');

    $this->service->handleWrongAnswer($quest, $progress, $checkpoint);
    $result = $this->service->handleWrongAnswer($quest, $progress, $checkpoint);

    expect($result['attempts'])->toBe(2);
});

it('lets the player retry for free', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('retry_free');

    expect($this->service->handleWrongAnswer($quest, $progress, $checkpoint))
        ->toBe(['behaviour' => 'retry_free', 'attempts' => 1]);
});

it('reports the points a wrong answer cost', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('retry_penalty', [
        'scoring_wrong_attempt_penalty_enabled' => true,
        'wrong_answer_penalty_points' => 25,
    ]);
    $progress->update(['points_earned' => 100]);

    $result = $this->service->handleWrongAnswer($quest, $progress, $checkpoint);

    expect($result['behaviour'])->toBe('retry_penalty')
        ->and($result['penalty'])->toBe(25);
});

it('locks the question for the configured time', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('lockout', [
        'wrong_answer_lockout_seconds' => 60,
    ]);

    $result = $this->service->handleWrongAnswer($quest, $progress, $checkpoint);

    expect($result['behaviour'])->toBe('lockout')
        ->and(now()->diffInSeconds(Carbon\Carbon::parse($result['locked_until'])))
        ->toBeGreaterThan(55);
});

it('falls back to a thirty second lockout when none is configured', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('lockout', [
        'wrong_answer_lockout_seconds' => null,
    ]);

    $result = $this->service->handleWrongAnswer($quest, $progress, $checkpoint);

    expect(now()->diffInSeconds(Carbon\Carbon::parse($result['locked_until'])))
        ->toBeGreaterThan(25)
        ->toBeLessThanOrEqual(30);
});

it('withholds the hint for the first two wrong answers', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('three_strikes_hint');

    expect($this->service->handleWrongAnswer($quest, $progress, $checkpoint))
        ->not->toHaveKey('hint');
    expect($this->service->handleWrongAnswer($quest, $progress, $checkpoint))
        ->not->toHaveKey('hint');
});

it('reveals the hint on the third wrong answer', function () {
    [$quest, $progress, $checkpoint] = wrongAnswerSetup('three_strikes_hint');

    $this->service->handleWrongAnswer($quest, $progress, $checkpoint);
    $this->service->handleWrongAnswer($quest, $progress, $checkpoint);
    $result = $this->service->handleWrongAnswer($quest, $progress, $checkpoint);

    expect($result['hint'])->toBe('Look above the door');
});
