<?php

use App\Enums\PlayMode;
use App\Enums\QuestStatus;
use App\Enums\SessionStatus;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->player = User::factory()->create();
    $this->quest = Quest::factory()->create([
        'status' => QuestStatus::Published,
    ]);
});

it('creates a session', function () {
    $response = $this->actingAs($this->host)->postJson('/api/v1/sessions', [
        'quest_id' => $this->quest->id,
        'play_mode' => PlayMode::Competitive->value,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['session']);

    $this->assertDatabaseHas('quest_sessions', [
        'quest_id' => $this->quest->id,
        'host_id' => $this->host->id,
        'status' => SessionStatus::Waiting->value,
    ]);

    $session = QuestSession::first();
    expect($session->join_code)->toMatch('/^[A-Z0-9]{6}$/');
});

it('shows a session by code', function () {
    $session = QuestSession::factory()->create();

    $response = $this->getJson("/api/v1/sessions/{$session->join_code}");

    $response->assertOk()
        ->assertJsonPath('data.join_code', $session->join_code);
});

it('joins a waiting session', function () {
    Event::fake();
    $session = QuestSession::factory()->create([
        'status' => SessionStatus::Waiting,
    ]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Player One',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('participant.display_name', 'Player One');

    $this->assertDatabaseHas('session_participants', [
        'quest_session_id' => $session->id,
        'user_id' => $this->player->id,
        'display_name' => 'Player One',
    ]);
});

it('cannot join an in-progress session', function () {
    $session = QuestSession::factory()->create([
        'status' => SessionStatus::InProgress,
    ]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Late Player',
    ]);

    $response->assertStatus(404);
});

it('enforces one active session per player', function () {
    Event::fake();
    $session1 = QuestSession::factory()->create(['status' => SessionStatus::Waiting]);
    $session2 = QuestSession::factory()->create(['status' => SessionStatus::Waiting]);

    // Join first session
    SessionParticipant::factory()->create([
        'quest_session_id' => $session1->id,
        'user_id' => $this->player->id,
    ]);

    // Try to join second session
    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session2->join_code}/join", [
        'display_name' => 'Player One',
    ]);

    $response->assertStatus(409);
});

it('allows joining after previous session completes', function () {
    Event::fake();
    $completedSession = QuestSession::factory()->create([
        'status' => SessionStatus::Completed,
    ]);
    SessionParticipant::factory()->create([
        'quest_session_id' => $completedSession->id,
        'user_id' => $this->player->id,
    ]);

    $newSession = QuestSession::factory()->create(['status' => SessionStatus::Waiting]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$newSession->join_code}/join", [
        'display_name' => 'Player One',
    ]);

    $response->assertStatus(201);
});

it('enforces max participants', function () {
    Event::fake();
    $quest = Quest::factory()->create(['max_participants' => 1]);
    $session = QuestSession::factory()->create([
        'quest_id' => $quest->id,
        'status' => SessionStatus::Waiting,
    ]);
    SessionParticipant::factory()->create(['quest_session_id' => $session->id]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Overflow',
    ]);

    $response->assertStatus(422);
});

it('host starts session', function () {
    Event::fake();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Waiting,
    ]);

    $response = $this->actingAs($this->host)->postJson("/api/v1/sessions/{$session->join_code}/start");

    $response->assertOk();
    $session->refresh();
    expect($session->status)->toBe(SessionStatus::InProgress);
    expect($session->started_at)->not->toBeNull();
});

it('non-host cannot start session', function () {
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Waiting,
    ]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session->join_code}/start");

    $response->assertStatus(403);
});

it('host ends session', function () {
    Event::fake();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::InProgress,
        'started_at' => now(),
    ]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
    ]);

    $response = $this->actingAs($this->host)->postJson("/api/v1/sessions/{$session->join_code}/end");

    $response->assertOk();
    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed);
    expect($session->completed_at)->not->toBeNull();

    // Unfinished participants get finished_at set
    $participant->refresh();
    expect($participant->finished_at)->not->toBeNull();
});

it('non-host cannot end session', function () {
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::InProgress,
    ]);

    $response = $this->actingAs($this->player)->postJson("/api/v1/sessions/{$session->join_code}/end");

    $response->assertStatus(403);
});

it('host accesses dashboard', function () {
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
    ]);

    $response = $this->actingAs($this->host)->getJson("/api/v1/sessions/{$session->join_code}/dashboard");

    $response->assertOk()
        ->assertJsonStructure(['session']);
});

it('non-host cannot access dashboard', function () {
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
    ]);

    $response = $this->actingAs($this->player)->getJson("/api/v1/sessions/{$session->join_code}/dashboard");

    $response->assertStatus(403);
});
