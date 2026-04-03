<?php

use App\Enums\PlayMode;
use App\Enums\QuestStatus;
use App\Enums\SessionStatus;
use App\Events\ParticipantJoined;
use App\Events\SessionEnded;
use App\Events\SessionStarted;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->quest = Quest::factory()->create([
        'status' => QuestStatus::Published,
    ]);
});

it('creates a session', function () {
    $response = $this->actingAs($this->host)->postJson('/api/v1/sessions', [
        'quest_id' => $this->quest->id,
        'play_mode' => PlayMode::CompetitiveIndividual->value,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'session_code', 'status', 'play_mode', 'quest', 'host']]);

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
        ->assertJsonPath('data.session_code', $session->join_code);
});

it('joins a waiting session without auth', function () {
    Event::fake();
    $session = QuestSession::factory()->create(['status' => SessionStatus::Waiting]);

    $response = $this->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Player One',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.display_name', 'Player One')
        ->assertJsonPath('data.session_code', $session->join_code);

    $this->assertDatabaseHas('session_participants', [
        'quest_session_id' => $session->id,
        'display_name' => 'Player One',
    ]);

    Event::assertDispatched(ParticipantJoined::class, function ($event) use ($session) {
        return $event->sessionCode === $session->join_code
            && $event->participant->display_name === 'Player One';
    });
});

it('joins with optional user_id', function () {
    Event::fake();
    $user = User::factory()->create();
    $session = QuestSession::factory()->create(['status' => SessionStatus::Waiting]);

    $response = $this->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Player One',
        'user_id' => $user->id,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('session_participants', [
        'quest_session_id' => $session->id,
        'user_id' => $user->id,
    ]);
});

it('cannot join an active session', function () {
    $session = QuestSession::factory()->create(['status' => SessionStatus::Active]);

    $response = $this->postJson("/api/v1/sessions/{$session->join_code}/join", [
        'display_name' => 'Late Player',
    ]);

    $response->assertStatus(404);
});

it('host starts session', function () {
    Event::fake();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Waiting,
    ]);

    $response = $this->actingAs($this->host)->postJson("/api/v1/sessions/{$session->join_code}/start");

    $response->assertOk()
        ->assertJsonPath('data.status', SessionStatus::Active->value);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Active);
    expect($session->started_at)->not->toBeNull();

    Event::assertDispatched(SessionStarted::class, function ($event) use ($session) {
        return $event->session->id === $session->id;
    });
});

it('non-host cannot start session', function () {
    $player = User::factory()->create();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Waiting,
    ]);

    $response = $this->actingAs($player)->postJson("/api/v1/sessions/{$session->join_code}/start");

    $response->assertStatus(403);
});

it('host ends session', function () {
    Event::fake();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Active,
        'started_at' => now(),
    ]);
    $participant = SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
    ]);

    $response = $this->actingAs($this->host)->postJson("/api/v1/sessions/{$session->join_code}/end");

    $response->assertOk()
        ->assertJsonPath('data.status', SessionStatus::Completed->value);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed);
    expect($session->completed_at)->not->toBeNull();

    $participant->refresh();
    expect($participant->finished_at)->not->toBeNull();

    Event::assertDispatched(SessionEnded::class, function ($event) use ($session) {
        return $event->session->id === $session->id;
    });
});

it('non-host cannot end session', function () {
    $player = User::factory()->create();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
        'status' => SessionStatus::Active,
    ]);

    $response = $this->actingAs($player)->postJson("/api/v1/sessions/{$session->join_code}/end");

    $response->assertStatus(403);
});

it('host accesses dashboard', function () {
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
    ]);

    $response = $this->actingAs($this->host)->getJson("/api/v1/sessions/{$session->join_code}/dashboard");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['session', 'participants']]);
});

it('non-host cannot access dashboard', function () {
    $player = User::factory()->create();
    $session = QuestSession::factory()->create([
        'host_id' => $this->host->id,
    ]);

    $response = $this->actingAs($player)->getJson("/api/v1/sessions/{$session->join_code}/dashboard");

    $response->assertStatus(403);
});
