<?php

use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Models\Checkpoint;
use App\Models\Quest;

// Copenhagen center: 55.6761, 12.5683
// ~1km away: 55.6851, 12.5683
// ~5km away: 55.7211, 12.5683
// ~60km away: 56.1561, 12.5683

function createPublishedQuestWithCheckpoints(array $checkpoints): Quest
{
    $quest = Quest::factory()->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
        'published_at' => now(),
    ]);

    foreach ($checkpoints as $i => $coords) {
        Checkpoint::factory()->create([
            'quest_id' => $quest->id,
            'latitude' => $coords[0],
            'longitude' => $coords[1],
            'sort_order' => $i,
        ]);
    }

    return $quest;
}

it('requires latitude and longitude', function () {
    $this->getJson('/api/v1/quests/nearby')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('validates latitude and longitude ranges', function () {
    $this->getJson('/api/v1/quests/nearby?latitude=91&longitude=181')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('returns nearby quests sorted by distance', function () {
    $farQuest = createPublishedQuestWithCheckpoints([
        [55.7211, 12.5683], // ~5km from center
    ]);

    $nearQuest = createPublishedQuestWithCheckpoints([
        [55.6851, 12.5683], // ~1km from center
    ]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $data = $response->json('data');
    expect($data[0]['id'])->toBe($nearQuest->id)
        ->and($data[1]['id'])->toBe($farQuest->id)
        ->and($data[0]['distance_to_start_km'])->toBeLessThan($data[1]['distance_to_start_km']);
});

it('filters quests by radius', function () {
    createPublishedQuestWithCheckpoints([
        [55.6851, 12.5683], // ~1km from center
    ]);

    createPublishedQuestWithCheckpoints([
        [55.7211, 12.5683], // ~5km from center
    ]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683&radius=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('excludes quests outside the default 50km radius', function () {
    createPublishedQuestWithCheckpoints([
        [56.1561, 12.5683], // ~60km from center
    ]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('excludes non-published and non-public quests', function () {
    // Draft quest
    $draft = Quest::factory()->create(['status' => QuestStatus::Draft, 'visibility' => QuestVisibility::Public]);
    Checkpoint::factory()->create(['quest_id' => $draft->id, 'latitude' => 55.6851, 'longitude' => 12.5683, 'sort_order' => 0]);

    // Private quest
    $private = Quest::factory()->create(['status' => QuestStatus::Published, 'visibility' => QuestVisibility::Private, 'published_at' => now()]);
    Checkpoint::factory()->create(['quest_id' => $private->id, 'latitude' => 55.6851, 'longitude' => 12.5683, 'sort_order' => 0]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns starting checkpoint and distance fields', function () {
    $quest = createPublishedQuestWithCheckpoints([
        [55.6851, 12.5683], // start ~1km
        [55.7211, 12.5683], // second ~5km
    ]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683');

    $response->assertOk();

    $data = $response->json('data.0');

    expect($data)->toHaveKeys([
        'starting_checkpoint',
        'distance_to_start_km',
        'distance_to_farthest_km',
        'total_route_distance_km',
        'checkpoint_count',
    ]);

    expect($data['starting_checkpoint'])->toHaveKeys(['id', 'title', 'latitude', 'longitude']);
    expect($data['checkpoint_count'])->toBe(2);
    expect($data['distance_to_start_km'])->toBeGreaterThan(0);
    expect($data['distance_to_farthest_km'])->toBeGreaterThanOrEqual($data['distance_to_start_km']);
    expect($data['total_route_distance_km'])->toBeGreaterThan(0);
});

it('filters nearby quests by category', function () {
    $quest = createPublishedQuestWithCheckpoints([
        [55.6851, 12.5683],
    ]);

    $otherQuest = createPublishedQuestWithCheckpoints([
        [55.6851, 12.5683],
    ]);

    $response = $this->getJson("/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683&category_id={$quest->category_id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.id'))->toBe($quest->id);
});

it('filters nearby quests by difficulty', function () {
    $easyQuest = Quest::factory()->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
        'published_at' => now(),
        'difficulty' => Difficulty::Easy,
    ]);
    Checkpoint::factory()->create(['quest_id' => $easyQuest->id, 'latitude' => 55.6851, 'longitude' => 12.5683, 'sort_order' => 0]);

    $hardQuest = Quest::factory()->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
        'published_at' => now(),
        'difficulty' => Difficulty::Hard,
    ]);
    Checkpoint::factory()->create(['quest_id' => $hardQuest->id, 'latitude' => 55.6851, 'longitude' => 12.5683, 'sort_order' => 0]);

    $response = $this->getJson('/api/v1/quests/nearby?latitude=55.6761&longitude=12.5683&difficulty=easy');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.id'))->toBe($easyQuest->id);
});
