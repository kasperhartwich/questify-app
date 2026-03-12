<?php

use App\Enums\WrongAnswerBehaviour;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Services\ScoringService;

beforeEach(function () {
    $this->service = new ScoringService;
});

// --- calculateAnswerScore ---

it('returns 0 for incorrect answer', function () {
    $question = Question::factory()->make(['points' => 10]);

    expect($this->service->calculateAnswerScore($question, 5, false))->toBe(0);
});

it('calculates base points for correct answer', function () {
    $question = Question::factory()->make(['points' => 10]);

    // At exactly 30 seconds - no speed bonus
    expect($this->service->calculateAnswerScore($question, 30, true))->toBe(10);
});

it('gives max speed bonus at 0 seconds', function () {
    $question = Question::factory()->make(['points' => 10]);

    // 10 base + 50 max speed bonus = 60
    expect($this->service->calculateAnswerScore($question, 0, true))->toBe(60);
});

it('gives half speed bonus at 15 seconds', function () {
    $question = Question::factory()->make(['points' => 10]);

    // 10 base + 25 speed bonus = 35
    expect($this->service->calculateAnswerScore($question, 15, true))->toBe(35);
});

it('gives no speed bonus at 30 seconds', function () {
    $question = Question::factory()->make(['points' => 10]);

    expect($this->service->calculateAnswerScore($question, 30, true))->toBe(10);
});

it('caps speed bonus at 30 seconds even for longer times', function () {
    $question = Question::factory()->make(['points' => 10]);

    expect($this->service->calculateAnswerScore($question, 60, true))->toBe(10);
});

it('applies wrong attempt penalty', function () {
    $question = Question::factory()->make(['points' => 10]);

    // 10 base + 0 speed - 20 penalty = max(0, -10) = 0
    expect($this->service->calculateAnswerScore($question, 30, true, 2))->toBe(0);
});

it('applies wrong attempt penalty with speed bonus', function () {
    $question = Question::factory()->make(['points' => 10]);

    // 10 base + 50 speed - 10 penalty = 50
    expect($this->service->calculateAnswerScore($question, 0, true, 1))->toBe(50);
});

it('floors score at 0 when penalty exceeds points', function () {
    $question = Question::factory()->make(['points' => 5]);

    // 5 base + 0 speed - 30 penalty = max(0, -25) = 0
    expect($this->service->calculateAnswerScore($question, 30, true, 3))->toBe(0);
});

it('handles high base points', function () {
    $question = Question::factory()->make(['points' => 100]);

    // 100 base + 50 speed bonus = 150
    expect($this->service->calculateAnswerScore($question, 0, true))->toBe(150);
});

// --- calculateCompletionBonus ---

it('returns 0 if session not started', function () {
    $session = QuestSession::factory()->create(['started_at' => null]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => now(),
    ]);

    $quest = Quest::factory()->create();

    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(0);
});

it('returns 0 if participant not finished', function () {
    $session = QuestSession::factory()->create(['started_at' => now()]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => null,
    ]);

    $quest = Quest::factory()->create();

    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(0);
});

it('gives max completion bonus for instant finish', function () {
    $startedAt = now();
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt,
    ]);

    $quest = Quest::factory()->create(['time_limit_per_question' => 30]);
    $checkpoint = Checkpoint::factory()->create(['quest_id' => $quest->id]);
    Question::factory()->count(2)->create(['checkpoint_id' => $checkpoint->id]);

    $quest->load('checkpoints.questions');

    // 200 * (1 - 0/60) = 200
    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(200);
});

it('gives reduced completion bonus based on time', function () {
    $startedAt = now();
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt->copy()->addSeconds(30),
    ]);

    $quest = Quest::factory()->create(['time_limit_per_question' => 30]);
    $checkpoint = Checkpoint::factory()->create(['quest_id' => $quest->id]);
    Question::factory()->count(2)->create(['checkpoint_id' => $checkpoint->id]);

    $quest->load('checkpoints.questions');

    // 200 * (1 - 30/60) = 100
    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(100);
});

it('gives 0 completion bonus when time exceeds max', function () {
    $startedAt = now();
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt->copy()->addSeconds(120),
    ]);

    $quest = Quest::factory()->create(['time_limit_per_question' => 30]);
    $checkpoint = Checkpoint::factory()->create(['quest_id' => $quest->id]);
    Question::factory()->count(2)->create(['checkpoint_id' => $checkpoint->id]);

    $quest->load('checkpoints.questions');

    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(0);
});

it('defaults to 30s per question when no time limit set', function () {
    $startedAt = now();
    $session = QuestSession::factory()->create(['started_at' => $startedAt]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'finished_at' => $startedAt,
    ]);

    $quest = Quest::factory()->create(['time_limit_per_question' => null]);
    $checkpoint = Checkpoint::factory()->create(['quest_id' => $quest->id]);
    Question::factory()->create(['checkpoint_id' => $checkpoint->id]);

    $quest->load('checkpoints.questions');

    // maxTime = 30 * 1 = 30, ratio = 0/30 = 0, bonus = 200
    expect($this->service->calculateCompletionBonus($participant, $quest))->toBe(200);
});

// --- handleWrongAnswer ---

it('handles retry_free behaviour', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create();

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result)->toBe([
        'can_retry' => true,
        'penalty' => 0,
        'locked_out' => false,
        'hint' => null,
    ]);
});

it('handles retry_penalty behaviour with accumulated attempts', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::RetryPenalty]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create();

    CheckpointProgress::create([
        'session_participant_id' => $participant->id,
        'checkpoint_id' => $question->checkpoint_id,
        'question_id' => $question->id,
        'is_correct' => false,
        'points_earned' => 0,
        'time_taken_seconds' => 5,
        'wrong_attempts' => 2,
    ]);

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result['can_retry'])->toBeTrue();
    expect($result['penalty'])->toBe(20);
    expect($result['locked_out'])->toBeFalse();
    expect($result['hint'])->toBeNull();
});

it('handles lockout behaviour', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::Lockout]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create();

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result)->toBe([
        'can_retry' => false,
        'penalty' => 0,
        'locked_out' => true,
        'hint' => null,
    ]);
});

it('handles three_strikes_hint - first attempt no hint', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create(['hint' => 'Helpful hint']);

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result['can_retry'])->toBeTrue();
    expect($result['locked_out'])->toBeFalse();
    expect($result['hint'])->toBeNull();
});

it('handles three_strikes_hint - second attempt shows hint', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create(['hint' => 'Helpful hint']);

    CheckpointProgress::create([
        'session_participant_id' => $participant->id,
        'checkpoint_id' => $question->checkpoint_id,
        'question_id' => $question->id,
        'is_correct' => false,
        'points_earned' => 0,
        'time_taken_seconds' => 5,
        'wrong_attempts' => 2,
    ]);

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result['can_retry'])->toBeTrue();
    expect($result['hint'])->toBe('Helpful hint');
});

it('handles three_strikes_hint - third attempt locks out', function () {
    $quest = Quest::factory()->create(['wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint]);
    $participant = SessionParticipant::factory()->create();
    $question = Question::factory()->create(['hint' => 'Helpful hint']);

    CheckpointProgress::create([
        'session_participant_id' => $participant->id,
        'checkpoint_id' => $question->checkpoint_id,
        'question_id' => $question->id,
        'is_correct' => false,
        'points_earned' => 0,
        'time_taken_seconds' => 5,
        'wrong_attempts' => 3,
    ]);

    $result = $this->service->handleWrongAnswer($quest, $participant, $question);

    expect($result['can_retry'])->toBeFalse();
    expect($result['locked_out'])->toBeTrue();
    expect($result['hint'])->toBe('Helpful hint');
});
