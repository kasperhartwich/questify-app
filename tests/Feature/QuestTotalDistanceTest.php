<?php

use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Models\Category;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
});

it('calculates total distance when checkpoints are created', function () {
    $quest = Quest::factory()->create();

    // Copenhagen center
    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'sort_order' => 0,
    ]);

    // ~2km away
    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6900,
        'longitude' => 12.5683,
        'sort_order' => 1,
    ]);

    $quest->refresh();
    expect($quest->total_distance_km)->not->toBeNull()
        ->and((float) $quest->total_distance_km)->toBeGreaterThan(0);
});

it('recalculates distance when a checkpoint is updated', function () {
    $quest = Quest::factory()->create();

    $cp1 = Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'sort_order' => 0,
    ]);

    $cp2 = Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6900,
        'longitude' => 12.5683,
        'sort_order' => 1,
    ]);

    $quest->refresh();
    $originalDistance = (float) $quest->total_distance_km;

    // Move second checkpoint much farther away
    $cp2->update(['latitude' => 55.8000]);

    $quest->refresh();
    expect((float) $quest->total_distance_km)->toBeGreaterThan($originalDistance);
});

it('recalculates distance when a checkpoint is deleted', function () {
    $quest = Quest::factory()->create();

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'sort_order' => 0,
    ]);

    $cp2 = Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6900,
        'longitude' => 12.5683,
        'sort_order' => 1,
    ]);

    $quest->refresh();
    expect((float) $quest->total_distance_km)->toBeGreaterThan(0);

    $cp2->delete();

    $quest->refresh();
    expect((float) $quest->total_distance_km)->toBe(0.0);
});

it('sets distance to zero for a quest with one checkpoint', function () {
    $quest = Quest::factory()->create();

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'sort_order' => 0,
    ]);

    $quest->refresh();
    expect((float) $quest->total_distance_km)->toBe(0.0);
});

it('sets distance to zero for a quest with no checkpoints', function () {
    $quest = Quest::factory()->create();

    // Create and delete to trigger calculation
    $cp = Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'sort_order' => 0,
    ]);
    $cp->delete();

    $quest->refresh();
    expect((float) $quest->total_distance_km)->toBe(0.0);
});

it('includes total_distance_km in quest list response', function () {
    $quest = Quest::factory()->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
    ]);

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'sort_order' => 0,
    ]);

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6900,
        'longitude' => 12.5683,
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/quests');

    $response->assertOk()
        ->assertJsonPath('data.0.total_distance_km', fn ($v) => $v > 0);
});

it('includes total_distance_km in quest detail response', function () {
    $quest = Quest::factory()->create();

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'sort_order' => 0,
    ]);

    Checkpoint::factory()->create([
        'quest_id' => $quest->id,
        'latitude' => 55.6900,
        'longitude' => 12.5683,
        'sort_order' => 1,
    ]);

    $response = $this->getJson("/api/v1/quests/{$quest->id}");

    $response->assertOk()
        ->assertJsonPath('data.total_distance_km', fn ($v) => $v > 0);
});

it('calculates total distance when quest is created with checkpoints via API', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/quests', [
        'category_id' => $this->category->id,
        'title' => 'Distance Test Quest',
        'description' => 'Testing distance calculation',
        'difficulty' => 'medium',
        'visibility' => 'public',
        'estimated_duration_minutes' => 60,
        'checkpoints' => [
            [
                'title' => 'Start',
                'latitude' => 55.6761,
                'longitude' => 12.5683,
                'questions' => [
                    [
                        'question_text' => 'Q1?',
                        'question_type' => 'true_false',
                        'answers' => [
                            ['answer_text' => 'True', 'is_correct' => true],
                            ['answer_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'End',
                'latitude' => 55.6900,
                'longitude' => 12.5683,
                'questions' => [
                    [
                        'question_text' => 'Q2?',
                        'question_type' => 'true_false',
                        'answers' => [
                            ['answer_text' => 'True', 'is_correct' => true],
                            ['answer_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.total_distance_km', fn ($v) => $v > 0);
});
