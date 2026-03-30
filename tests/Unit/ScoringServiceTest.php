<?php

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
