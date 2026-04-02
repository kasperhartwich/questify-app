<?php

use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns user stats in me endpoint', function () {
    Quest::factory()->count(2)->create(['creator_id' => $this->user->id]);

    $sessions = QuestSession::factory()->count(3)->create();
    foreach ($sessions as $i => $session) {
        SessionParticipant::factory()->create([
            'quest_session_id' => $session->id,
            'user_id' => $this->user->id,
            'score' => ($i + 1) * 100,
        ]);
    }

    $response = $this->actingAs($this->user)->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.quests_created_count', 2)
        ->assertJsonPath('data.quests_played_count', 3)
        ->assertJsonPath('data.total_points', 600);
});

it('returns zero stats for new user', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.quests_created_count', 0)
        ->assertJsonPath('data.quests_played_count', 0)
        ->assertJsonPath('data.total_points', 0);
});
