<?php

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Enums\WrongAnswerBehaviour;
use App\Events\CheckpointArrived;
use App\Events\LeaderboardUpdated;
use App\Events\QuestCompleted;
use App\Events\SessionEnded;
use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->quest = Quest::factory()->create([
        'status' => 'published',
        'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree,
        'scoring_points_per_correct' => 100,
        'scoring_speed_bonus_enabled' => false,
        'scoring_wrong_attempt_penalty_enabled' => false,
    ]);
    $this->checkpoint = Checkpoint::factory()->create([
        'quest_id' => $this->quest->id,
        'sort_order' => 0,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);
    $this->question = Question::factory()->create([
        'checkpoint_id' => $this->checkpoint->id,
        'type' => QuestionType::MultipleChoice,
        'points' => 10,
    ]);
    $this->correctAnswer = Answer::factory()->correct()->create([
        'question_id' => $this->question->id,
    ]);
    $this->wrongAnswer = Answer::factory()->create([
        'question_id' => $this->question->id,
        'is_correct' => false,
    ]);

    $this->session = QuestSession::factory()->create([
        'quest_id' => $this->quest->id,
        'status' => SessionStatus::Active,
        'started_at' => now(),
    ]);
    $this->participant = SessionParticipant::factory()->create([
        'quest_session_id' => $this->session->id,
        'score' => 0,
    ]);
});

it('records arrival at checkpoint', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'title', 'order_index', 'questions']]);
});

it('exposes checkpoint coordinates on an active session (gameplay), not on a waiting one', function () {
    // Active session (from beforeEach) reveals checkpoint coordinates for gameplay.
    $active = $this->getJson("/api/v1/sessions/{$this->session->join_code}");
    $active->assertOk()
        ->assertJsonPath('data.checkpoints.0.id', $this->checkpoint->id);
    expect($active->json('data.checkpoints.0.latitude'))->not->toBeNull();

    // A session still in the lobby must NOT leak the route.
    $waiting = QuestSession::factory()->create([
        'quest_id' => $this->quest->id,
        'status' => SessionStatus::Waiting,
    ]);
    $this->getJson("/api/v1/sessions/{$waiting->join_code}")
        ->assertOk()
        ->assertJsonMissingPath('data.checkpoints');
});

it('rejects arrival when the player is outside the checkpoint radius', function () {
    // ~1.3 km north of the checkpoint (55.6761) — well beyond the 30 m default radius.
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6880,
        'longitude' => 12.5683,
    ]);

    $response->assertStatus(422);
});

it('accepts arrival just inside the checkpoint radius', function () {
    // ~11 m east of the checkpoint — inside the 30 m default radius.
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.56848,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'title', 'questions']]);
});

it('returns questions with admin API field names on arrival', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    $response->assertOk();
    $question = $response->json('data.questions.0');
    expect($question)->toHaveKeys(['id', 'question_text', 'question_type', 'answers']);
    expect($question['answers'][0])->toHaveKeys(['id', 'answer_text']);
});

it('submits correct answer', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', true)
        ->assertJsonPath('data.next', 'checkpoint_complete');

    expect($response->json('data.score_earned'))->toBeGreaterThan(0);
    expect($response->json('data.total_score'))->toBeGreaterThan(0);

    $this->assertDatabaseHas('checkpoint_progress', [
        'session_participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'is_correct' => true,
    ]);
});

it('submits wrong answer', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->wrongAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', false)
        ->assertJsonPath('data.behaviour', 'retry_free');
});

it('submits answer for open text question', function () {
    $otQuestion = Question::factory()->create([
        'checkpoint_id' => $this->checkpoint->id,
        'type' => QuestionType::OpenText,
    ]);
    Answer::factory()->correct()->create([
        'question_id' => $otQuestion->id,
        'body' => 'Copenhagen',
    ]);

    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $otQuestion->id,
        'answer_text' => 'copenhagen',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', true);
});

it('handles lockout wrong answer behaviour', function () {
    $this->quest->update(['wrong_answer_behaviour' => WrongAnswerBehaviour::Lockout]);

    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->wrongAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', false)
        ->assertJsonPath('data.behaviour', 'lockout');

    expect($response->json('data.locked_until'))->not->toBeNull();
});

it('retrieves leaderboard without auth', function () {
    SessionParticipant::factory()->create([
        'quest_session_id' => $this->session->id,
        'score' => 100,
    ]);
    $this->participant->update(['score' => 50]);

    $response = $this->getJson("/api/v1/sessions/{$this->session->join_code}/leaderboard");

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['total_score'])->toBe(100);
    expect($data[1]['total_score'])->toBe(50);
    expect($data[0])->toHaveKeys(['id', 'display_name', 'total_score', 'current_checkpoint_index', 'quest_completed_at']);
});

it('sends the quest base points with each question on arrival', function () {
    $this->quest->update(['scoring_points_per_correct' => 80]);

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ])->assertOk()->assertJsonPath('data.questions.0.points', 80);
});

it('detects quest completion', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', true);

    $this->participant->refresh();
    expect($this->participant->finished_at)->not->toBeNull();
});

it('completes the session when the only participant finishes the quest', function () {
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ])->assertOk();

    $this->session->refresh();
    expect($this->session->status)->toBe(SessionStatus::Completed)
        ->and($this->session->completed_at)->not->toBeNull();
    Event::assertDispatched(SessionEnded::class);
});

it('keeps a group session active until every participant has finished', function () {
    SessionParticipant::factory()->create(['quest_session_id' => $this->session->id]);

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ])->assertOk();

    expect($this->session->fresh()->status)->toBe(SessionStatus::Active);
});

it('takes no new answers once the session is completed', function () {
    $otherQuestion = Question::factory()->create(['checkpoint_id' => $this->checkpoint->id]);
    $otherAnswer = Answer::factory()->correct()->create(['question_id' => $otherQuestion->id]);
    $this->session->update(['status' => SessionStatus::Completed]);

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $otherQuestion->id,
        'answer_id' => $otherAnswer->id,
    ])->assertNotFound();
});

it('handles three_strikes_hint wrong answer behaviour', function () {
    $this->quest->update(['wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint]);
    $this->question->update(['hint' => null]);
    $this->checkpoint->update(['hint' => 'Look at the building']);

    $submitWrong = fn () => $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->wrongAnswer->id,
    ]);

    // First two wrong answers: no hint
    $response = $submitWrong();
    $response->assertOk()
        ->assertJsonPath('data.correct', false)
        ->assertJsonPath('data.behaviour', 'three_strikes_hint')
        ->assertJsonPath('data.attempts', 1);
    expect($response->json('data.hint'))->toBeNull();

    $response = $submitWrong();
    expect($response->json('data.hint'))->toBeNull();
    expect($response->json('data.attempts'))->toBe(2);

    // Third wrong answer: hint revealed
    $response = $submitWrong();
    $response->assertOk()
        ->assertJsonPath('data.attempts', 3)
        ->assertJsonPath('data.hint', 'Look at the building');
});

it('handles retry_penalty wrong answer behaviour', function () {
    $this->quest->update([
        'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryPenalty,
        'wrong_answer_penalty_points' => 10,
    ]);

    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->wrongAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', false)
        ->assertJsonPath('data.behaviour', 'retry_penalty')
        ->assertJsonPath('data.penalty', 10);
});

it('applies wrong attempt penalty to correct answer score', function () {
    $this->quest->update([
        'scoring_wrong_attempt_penalty_enabled' => true,
        'wrong_answer_penalty_points' => 20,
    ]);

    // Submit wrong answer first
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->wrongAnswer->id,
    ]);

    // Now submit correct answer — score should be reduced by penalty
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.correct', true);

    // Base is 100, minus 20 penalty for 1 wrong attempt = 80
    expect($response->json('data.score_earned'))->toBe(80);
});

it('broadcasts CheckpointArrived on arrival', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    $response->assertOk();
    Event::assertDispatched(CheckpointArrived::class, function ($event) {
        return $event->sessionCode === $this->session->join_code
            && $event->participant->id === $this->participant->id
            && $event->checkpoint->id === $this->checkpoint->id;
    });
});

it('broadcasts LeaderboardUpdated on correct answer', function () {
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ]);

    Event::assertDispatched(LeaderboardUpdated::class, function ($event) {
        return $event->sessionCode === $this->session->join_code;
    });
});

it('broadcasts QuestCompleted when quest finishes', function () {
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ]);

    Event::assertDispatched(QuestCompleted::class, function ($event) {
        return $event->sessionCode === $this->session->join_code
            && $event->participant->id === $this->participant->id;
    });
});

it('gameplay endpoints are public', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    $response->assertOk();
});

it('scores a replayed correct answer only once and fires its events once', function () {
    $payload = [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ];

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", $payload)
        ->assertOk()->assertJsonPath('data.score_earned', 100);
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", $payload)
        ->assertOk()->assertJsonPath('data.score_earned', 0)->assertJsonPath('data.total_score', 100)
        ->assertJsonPath('data.next', 'checkpoint_complete');

    $this->participant->refresh();
    expect($this->participant->score)->toBe(100)
        ->and($this->participant->current_checkpoint_index)->toBe(1);
    Event::assertDispatchedTimes(QuestCompleted::class, 1);
});

it('keeps one progress row per participant and question', function () {
    $payload = [
        'participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
    ];

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", $payload);
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", $payload);

    expect(CheckpointProgress::where('session_participant_id', $this->participant->id)->count())->toBe(1);

    expect(fn () => CheckpointProgress::create([
        'session_participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'question_id' => $this->question->id,
    ]))->toThrow(QueryException::class);
});

it('rejects an answer for a question from another quest', function () {
    $foreign = Question::factory()->create(['type' => QuestionType::MultipleChoice]);
    $foreignCorrect = Answer::factory()->correct()->create(['question_id' => $foreign->id]);

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
        'participant_id' => $this->participant->id,
        'question_id' => $foreign->id,
        'answer_id' => $foreignCorrect->id,
    ])->assertNotFound();

    expect($this->participant->refresh()->score)->toBe(0);
});

it('rejects arrival with a participant from another session', function () {
    $otherSession = QuestSession::factory()->create([
        'quest_id' => $this->quest->id,
        'status' => SessionStatus::Active,
        'started_at' => now(),
    ]);
    $outsider = SessionParticipant::factory()->create(['quest_session_id' => $otherSession->id]);

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $outsider->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ])->assertNotFound();
});

it('falls back to a 30 m arrival radius when the quest has none', function () {
    $this->quest->update(['checkpoint_arrival_radius_meters' => null]);
    $body = fn (float $latitude) => [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => $latitude,
        'longitude' => 12.5683,
    ];

    // 0.0002 deg is ~22 m, 0.0004 deg is ~44 m.
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", $body(55.6763))->assertOk();
    $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", $body(55.6765))->assertStatus(422);
});
