<?php

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Quest;
use App\Models\User;
use App\Services\ActivityLogService;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns activity feed for authenticated user', function () {
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);
    $service->log($this->user, ActivityType::QuestCreated, $quest, ['quest_title' => $quest->title]);
    $service->log($this->user, ActivityType::QuestPublished, $quest, ['quest_title' => $quest->title]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'type', 'title', 'subtitle', 'icon', 'metadata', 'created_at']]]);
});

it('returns only the authenticated user activities', function () {
    $otherUser = User::factory()->create();
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);

    $service->log($this->user, ActivityType::QuestCreated, $quest, ['quest_title' => 'My Quest']);
    $service->log($otherUser, ActivityType::QuestCreated, $quest, ['quest_title' => 'Other Quest']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.metadata.quest_title', 'My Quest');
});

it('returns activities in reverse chronological order', function () {
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);

    $older = $service->log($this->user, ActivityType::QuestCreated, $quest, ['quest_title' => 'First']);
    $older->update(['created_at' => now()->subDay()]);

    $newer = $service->log($this->user, ActivityType::QuestPublished, $quest, ['quest_title' => 'Second']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk();
    expect($response->json('data.0.type'))->toBe('quest_published');
    expect($response->json('data.1.type'))->toBe('quest_created');
});

it('cursor paginates activities', function () {
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);

    for ($i = 0; $i < 20; $i++) {
        $service->log($this->user, ActivityType::QuestCreated, $quest, ['quest_title' => "Quest {$i}"]);
    }

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk()
        ->assertJsonCount(15, 'data');
    expect($response->json('meta.next_cursor'))->not->toBeNull();
});

it('requires authentication for activity feed', function () {
    $response = $this->getJson('/api/v1/user/activities');

    $response->assertStatus(401);
});

it('logs activity when quest is created', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)->postJson('/api/v1/quests', [
        'title' => 'Activity Test Quest',
        'description' => 'Testing activity logging.',
        'category_id' => $category->id,
        'difficulty' => 'easy',
        'visibility' => 'private',
        'estimated_duration_minutes' => 30,
        'checkpoint_arrival_radius_meters' => 30,
        'wrong_answer_behaviour' => 'retry_free',
        'scoring_points_per_correct' => 100,
        'checkpoints' => [
            [
                'title' => 'Start',
                'latitude' => 55.6761,
                'longitude' => 12.5683,
                'questions' => [
                    [
                        'question_text' => 'Test?',
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

    $response->assertCreated();

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $this->user->id,
        'type' => ActivityType::QuestCreated->value,
    ]);
});

it('logs activity when quest is favourited', function () {
    $quest = Quest::factory()->create();

    $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/favourite");

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $this->user->id,
        'type' => ActivityType::QuestFavourited->value,
        'subject_type' => Quest::class,
        'subject_id' => $quest->id,
    ]);
});

it('does not log activity when quest is unfavourited', function () {
    $quest = Quest::factory()->create();
    $this->user->favouriteQuests()->attach($quest);

    $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/favourite");

    expect(ActivityLog::where('user_id', $this->user->id)->count())->toBe(0);
});

it('builds correct title and subtitle for completed quest', function () {
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);
    $service->log($this->user, ActivityType::QuestCompleted, $quest, [
        'quest_title' => 'Frederiksberg',
        'score' => 2340,
        'placement' => 1,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk();
    expect($response->json('data.0.title'))->toBe('Completed Frederiksberg quest');
    expect($response->json('data.0.subtitle'))->toBe('1st · 2,340 pts');
    expect($response->json('data.0.icon'))->toBe('checkmark');
});

it('builds correct title and subtitle for published quest', function () {
    $quest = Quest::factory()->create();
    $service = app(ActivityLogService::class);
    $service->log($this->user, ActivityType::QuestPublished, $quest, [
        'quest_title' => 'Byens Skjulte Perler',
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/user/activities');

    $response->assertOk();
    expect($response->json('data.0.title'))->toBe('Published new quest');
    expect($response->json('data.0.subtitle'))->toBe('Byens Skjulte Perler');
    expect($response->json('data.0.icon'))->toBe('map_pin');
});
