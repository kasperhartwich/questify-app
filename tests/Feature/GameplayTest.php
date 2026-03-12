<?php

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->player = User::factory()->create();
    $this->quest = Quest::factory()->create([
        'status' => 'published',
        'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree,
        'time_limit_per_question' => 30,
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
        'status' => SessionStatus::InProgress,
        'started_at' => now(),
    ]);
    $this->participant = SessionParticipant::factory()->create([
        'quest_session_id' => $this->session->id,
        'user_id' => $this->player->id,
        'score' => 0,
    ]);
});

it('records arrival at checkpoint', function () {
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/arrived");

    $response->assertOk()
        ->assertJsonStructure(['checkpoint']);
});

it('submits correct answer for multiple choice', function () {
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('is_correct', true)
        ->assertJsonPath('checkpoint_complete', true);

    expect($response->json('points_earned'))->toBeGreaterThan(0);

    $this->assertDatabaseHas('checkpoint_progress', [
        'session_participant_id' => $this->participant->id,
        'question_id' => $this->question->id,
        'is_correct' => true,
    ]);
});

it('submits wrong answer for multiple choice', function () {
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('is_correct', false)
        ->assertJsonPath('points_earned', 0);
});

it('submits answer for true/false question', function () {
    $tfQuestion = Question::factory()->create([
        'checkpoint_id' => $this->checkpoint->id,
        'type' => QuestionType::TrueFalse,
        'points' => 5,
    ]);
    $trueAnswer = Answer::factory()->correct()->create([
        'question_id' => $tfQuestion->id,
        'body' => 'True',
    ]);
    Answer::factory()->create([
        'question_id' => $tfQuestion->id,
        'body' => 'False',
        'is_correct' => false,
    ]);

    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$tfQuestion->id}/answer", [
            'answer_id' => $trueAnswer->id,
            'time_taken_seconds' => 3,
        ]);

    $response->assertOk()
        ->assertJsonPath('is_correct', true);
});

it('submits answer for open-ended question', function () {
    $oeQuestion = Question::factory()->create([
        'checkpoint_id' => $this->checkpoint->id,
        'type' => QuestionType::OpenEnded,
        'points' => 15,
    ]);

    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$oeQuestion->id}/answer", [
            'open_ended_answer' => 'My thoughtful answer',
            'time_taken_seconds' => 20,
        ]);

    $response->assertOk()
        ->assertJsonPath('is_correct', true); // Open-ended always correct

    $this->assertDatabaseHas('checkpoint_progress', [
        'question_id' => $oeQuestion->id,
        'open_ended_answer' => 'My thoughtful answer',
        'is_correct' => true,
    ]);
});

it('prevents re-answering correctly answered question', function () {
    CheckpointProgress::create([
        'session_participant_id' => $this->participant->id,
        'checkpoint_id' => $this->checkpoint->id,
        'question_id' => $this->question->id,
        'answer_id' => $this->correctAnswer->id,
        'is_correct' => true,
        'points_earned' => 50,
        'time_taken_seconds' => 5,
    ]);

    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 1,
        ]);

    $response->assertOk()
        ->assertJsonPath('points_earned', 50); // Returns existing score
});

it('handles retry_free wrong answer behaviour', function () {
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('wrong_answer.can_retry', true)
        ->assertJsonPath('wrong_answer.penalty', 0)
        ->assertJsonPath('wrong_answer.locked_out', false);
});

it('handles retry_penalty wrong answer behaviour', function () {
    $this->quest->update(['wrong_answer_behaviour' => WrongAnswerBehaviour::RetryPenalty]);

    // First wrong attempt
    $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    // Second wrong attempt
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('wrong_answer.can_retry', true)
        ->assertJsonPath('wrong_answer.locked_out', false);
    expect($response->json('wrong_answer.penalty'))->toBeGreaterThan(0);
});

it('handles lockout wrong answer behaviour', function () {
    $this->quest->update(['wrong_answer_behaviour' => WrongAnswerBehaviour::Lockout]);

    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('wrong_answer.can_retry', false)
        ->assertJsonPath('wrong_answer.locked_out', true);
});

it('handles three_strikes_hint wrong answer behaviour', function () {
    $this->quest->update(['wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint]);
    $this->question->update(['hint' => 'Here is a hint']);

    // First two wrong attempts - no hint
    $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('wrong_answer.can_retry', true)
        ->assertJsonPath('wrong_answer.hint', 'Here is a hint');

    // Third wrong attempt - locked out
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->wrongAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('wrong_answer.can_retry', false)
        ->assertJsonPath('wrong_answer.locked_out', true);
});

it('calculates score with speed bonus', function () {
    // Answer instantly (0 seconds) - should get max speed bonus
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 0,
        ]);

    // 10 base points + 50 speed bonus = 60
    $response->assertJsonPath('points_earned', 60);
});

it('reduces speed bonus over time', function () {
    // Answer at 15 seconds - should get half speed bonus
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 15,
        ]);

    // 10 base + 25 speed bonus = 35
    $response->assertJsonPath('points_earned', 35);
});

it('gives no speed bonus after 30 seconds', function () {
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 30,
        ]);

    // 10 base + 0 speed bonus = 10
    $response->assertJsonPath('points_earned', 10);
});

it('retrieves leaderboard', function () {
    $player2 = User::factory()->create();
    SessionParticipant::factory()->create([
        'quest_session_id' => $this->session->id,
        'user_id' => $player2->id,
        'score' => 100,
    ]);
    $this->participant->update(['score' => 50]);

    $response = $this->actingAs($this->player)
        ->getJson("/api/v1/sessions/{$this->session->join_code}/leaderboard");

    $response->assertOk()
        ->assertJsonStructure(['leaderboard']);

    $leaderboard = $response->json('leaderboard');
    // LeaderboardEntryResource collection nested in json response
    $data = is_array($leaderboard) && isset($leaderboard['data']) ? $leaderboard['data'] : $leaderboard;
    expect($data[0]['score'])->toBe(100);
    expect($data[1]['score'])->toBe(50);
});

it('detects quest completion', function () {
    // Only one question in this quest (from beforeEach)
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('quest_complete', true);

    $this->participant->refresh();
    expect($this->participant->finished_at)->not->toBeNull();
});

it('does not mark quest complete when questions remain', function () {
    // Add a second question
    $q2 = Question::factory()->create([
        'checkpoint_id' => $this->checkpoint->id,
        'type' => QuestionType::MultipleChoice,
        'points' => 10,
    ]);
    Answer::factory()->correct()->create(['question_id' => $q2->id]);

    // Answer only first question
    $response = $this->actingAs($this->player)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/questions/{$this->question->id}/answer", [
            'answer_id' => $this->correctAnswer->id,
            'time_taken_seconds' => 5,
        ]);

    $response->assertOk()
        ->assertJsonPath('quest_complete', false);
});

it('requires authentication for gameplay', function () {
    $response = $this->postJson("/api/v1/sessions/{$this->session->join_code}/checkpoints/{$this->checkpoint->id}/arrived");

    $response->assertStatus(401);
});
