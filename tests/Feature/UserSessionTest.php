<?php

use App\Enums\SessionStatus;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/user/sessions')->assertStatus(401);
});

it('returns session participation history', function () {
    $session = QuestSession::factory()->create([
        'status' => SessionStatus::Completed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'user_id' => $this->user->id,
        'display_name' => 'TestPlayer',
        'score' => 250,
        'finished_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/sessions');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data' => [['participant_id', 'display_name', 'total_score', 'quest_completed_at', 'session']]]);

    $item = $response->json('data.0');
    expect($item['display_name'])->toBe('TestPlayer');
    expect($item['total_score'])->toBe(250);
    expect($item['session'])->toHaveKeys(['id', 'session_code', 'status', 'play_mode', 'quest', 'started_at', 'completed_at']);
});

it('excludes sessions from other users', function () {
    $otherUser = User::factory()->create();
    $session = QuestSession::factory()->create();

    SessionParticipant::factory()->create([
        'quest_session_id' => $session->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/sessions');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('orders sessions by latest first', function () {
    $sessionOld = QuestSession::factory()->create();
    $sessionNew = QuestSession::factory()->create();

    SessionParticipant::factory()->create([
        'quest_session_id' => $sessionOld->id,
        'user_id' => $this->user->id,
        'display_name' => 'OldSession',
        'created_at' => now()->subDay(),
    ]);

    SessionParticipant::factory()->create([
        'quest_session_id' => $sessionNew->id,
        'user_id' => $this->user->id,
        'display_name' => 'NewSession',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/sessions');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['display_name'])->toBe('NewSession');
    expect($data[1]['display_name'])->toBe('OldSession');
});
