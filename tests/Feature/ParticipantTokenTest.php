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
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * questify-app's own copy of the gameplay API follows questify-admin's
 * participant tokens (QU-40).
 */
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
    $this->correctAnswer = Answer::factory()->correct()->create(['question_id' => $this->question->id]);

    $this->session = QuestSession::factory()->create([
        'quest_id' => $this->quest->id,
        'status' => SessionStatus::Active,
        'started_at' => now(),
    ]);
});

/**
 * @param  array<string, mixed>  $attributes
 * @return array{0: SessionParticipant, 1: string}
 */
function appParticipantWithToken(QuestSession $session, array $attributes = []): array
{
    $participant = SessionParticipant::factory()->make(['quest_session_id' => $session->id, 'score' => 0, ...$attributes]);
    $token = $participant->issueToken();
    $participant->save();

    return [$participant, $token];
}

it('hands out a participant token at join and stores only its hash', function () {
    $session = QuestSession::factory()->create(['quest_id' => $this->quest->id]);

    $response = $this->withHeader('X-App-Build', '47')
        ->postJson("/api/v1/sessions/{$session->join_code}/join", ['display_name' => 'Ada'])
        ->assertOk();

    $token = $response->json('data.participant_token');

    expect($token)->toBeString()->toHaveLength(64)
        ->and(SessionParticipant::find($response->json('data.participant_id'))->token_hash)->toBe(hash('sha256', $token));
});

it('refuses to arrive or answer as another participant', function () {
    [, $attackerToken] = appParticipantWithToken($this->session);
    [$victim] = appParticipantWithToken($this->session);

    $this->withHeader('X-Participant-Token', $attackerToken)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", [
            'participant_id' => $victim->id,
            'checkpoint_id' => $this->checkpoint->id,
            'latitude' => 55.6761,
            'longitude' => 12.5683,
        ])
        ->assertForbidden();

    $this->withHeader('X-Participant-Token', $attackerToken)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
            'participant_id' => $victim->id,
            'question_id' => $this->question->id,
            'answer_id' => $this->correctAnswer->id,
        ])
        ->assertForbidden();

    expect($victim->fresh()->score)->toBe(0);
    $this->assertDatabaseMissing('checkpoint_progress', ['session_participant_id' => $victim->id]);
});

it('scores an answer sent with the participant\'s own token', function () {
    [$participant, $token] = appParticipantWithToken($this->session);

    $this->withHeader('X-Participant-Token', $token)
        ->postJson("/api/v1/sessions/{$this->session->join_code}/answer", [
            'participant_id' => $participant->id,
            'question_id' => $this->question->id,
            'answer_id' => $this->correctAnswer->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.correct', true);
});

it('lets a guest on an older build join after the deploy and play without a token', function (?string $build) {
    $session = QuestSession::factory()->create(['quest_id' => $this->quest->id]);
    $response = $this->withHeaders($build === null ? [] : ['X-App-Build' => $build])
        ->postJson("/api/v1/sessions/{$session->join_code}/join", ['display_name' => 'Old build'])
        ->assertOk();
    $session->update(['status' => SessionStatus::Active, 'started_at' => now()]);

    expect($response->json('data.participant_token'))->toBeNull()
        ->and(SessionParticipant::find($response->json('data.participant_id'))->hasToken())->toBeFalse();

    $this->withoutHeader('X-App-Build')
        ->postJson("/api/v1/sessions/{$session->join_code}/arrived", [
            'participant_id' => $response->json('data.participant_id'),
            'checkpoint_id' => $this->checkpoint->id,
            'latitude' => 55.6761,
            'longitude' => 12.5683,
        ])->assertOk();
})->with(['build 46' => '46', 'no build header' => null]);

it('gives a guest on build 47 a token and refuses it without one', function () {
    $session = QuestSession::factory()->create(['quest_id' => $this->quest->id]);
    $response = $this->withHeader('X-App-Build', '47')
        ->postJson("/api/v1/sessions/{$session->join_code}/join", ['display_name' => 'New build'])
        ->assertOk();
    $session->update(['status' => SessionStatus::Active, 'started_at' => now()]);

    expect($response->json('data.participant_token'))->toBeString();

    $this->withoutHeader('X-App-Build')
        ->postJson("/api/v1/sessions/{$session->join_code}/arrived", [
            'participant_id' => $response->json('data.participant_id'),
            'checkpoint_id' => $this->checkpoint->id,
            'latitude' => 55.6761,
            'longitude' => 12.5683,
        ])->assertForbidden();
});

it('lets a participant without a token play until tokens are required', function () {
    $legacy = SessionParticipant::factory()->create(['quest_session_id' => $this->session->id]);
    $payload = [
        'participant_id' => $legacy->id,
        'checkpoint_id' => $this->checkpoint->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ];

    $this->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", $payload)->assertOk();

    $this->withHeader('X-Participant-Token', 'not-a-token')
        ->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", $payload)
        ->assertForbidden();

    config(['questify.require_participant_token' => true]);

    $this->withoutHeader('X-Participant-Token')
        ->postJson("/api/v1/sessions/{$this->session->join_code}/arrived", $payload)
        ->assertForbidden();
});

it('shows no linked account, email or other account id in the session preview', function () {
    $host = $this->session->host;
    $stranger = User::factory()->create(['email' => 'stranger@example.com']);
    SessionParticipant::factory()->create(['quest_session_id' => $this->session->id, 'user_id' => $host->id]);
    SessionParticipant::factory()->create(['quest_session_id' => $this->session->id, 'user_id' => $stranger->id]);

    $response = $this->getJson("/api/v1/sessions/{$this->session->join_code}")->assertOk();

    expect($response->getContent())->not->toContain('stranger@example.com')
        ->and(collect($response->json('data.participants'))->pluck('user_id')->filter()->values()->all())->toBe([$host->id])
        ->and($response->json('data.participants.0'))->not->toHaveKey('user');
});

it('keeps the player list and leaderboard to the session once tokens are required', function () {
    config(['questify.require_participant_token' => true]);
    [, $token] = appParticipantWithToken($this->session);

    $this->getJson("/api/v1/sessions/{$this->session->join_code}")
        ->assertOk()
        ->assertJsonPath('data.participants_count', 1)
        ->assertJsonMissingPath('data.participants');
    $this->getJson("/api/v1/sessions/{$this->session->join_code}/leaderboard")->assertForbidden();

    $this->withHeader('X-Participant-Token', $token)
        ->getJson("/api/v1/sessions/{$this->session->join_code}")
        ->assertJsonCount(1, 'data.participants');
    $this->withHeader('X-Participant-Token', $token)
        ->getJson("/api/v1/sessions/{$this->session->join_code}/leaderboard")
        ->assertOk();
});
