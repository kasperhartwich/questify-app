<?php

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
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

it('gameplay endpoints are public', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
        'participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    $response->assertOk();
});
