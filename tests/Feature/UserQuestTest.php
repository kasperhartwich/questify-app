<?php

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\QuestRating;
use App\Models\QuestSession;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/user/quests')->assertStatus(401);
});

it('returns created quests for the authenticated user', function () {
    Quest::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'My Quest',
        'status' => QuestStatus::Published,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/quests');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'My Quest');
});

it('excludes quests from other users', function () {
    $otherUser = User::factory()->create();
    Quest::factory()->create(['creator_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/quests');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('includes ratings and session counts', function () {
    $quest = Quest::factory()->create([
        'creator_id' => $this->user->id,
        'status' => QuestStatus::Published,
    ]);

    QuestRating::factory()->create(['quest_id' => $quest->id, 'rating' => 4]);
    QuestRating::factory()->create(['quest_id' => $quest->id, 'rating' => 5]);
    QuestSession::factory()->create(['quest_id' => $quest->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/quests');

    $response->assertOk();
    $data = $response->json('data.0');
    expect($data['average_rating'])->toBe(4.5);
    expect($data['sessions_count'])->toBe(1);
});

it('returns quests ordered by latest first', function () {
    Quest::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Quest',
        'created_at' => now()->subDay(),
    ]);
    Quest::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'New Quest',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/quests');

    $response->assertOk();
    expect($response->json('data.0.title'))->toBe('New Quest');
    expect($response->json('data.1.title'))->toBe('Old Quest');
});
