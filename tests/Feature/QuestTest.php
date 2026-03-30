<?php

use App\Enums\Difficulty;
use App\Enums\ModerationStatus;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Models\Category;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
});

it('lists published public quests without auth', function () {
    Quest::factory()->count(3)->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Public,
        'published_at' => now(),
    ]);
    Quest::factory()->create(['status' => QuestStatus::Draft]);
    Quest::factory()->create([
        'status' => QuestStatus::Published,
        'visibility' => QuestVisibility::Private,
    ]);

    $response = $this->getJson('/api/v1/quests');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a quest with nested checkpoints', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/quests', [
        'category_id' => $this->category->id,
        'title' => 'My Quest',
        'description' => 'A test quest',
        'difficulty' => Difficulty::Medium->value,
        'visibility' => QuestVisibility::Public->value,
        'estimated_duration_minutes' => 60,
        'checkpoints' => [
            [
                'title' => 'First Stop',
                'latitude' => 55.6761,
                'longitude' => 12.5683,
                'questions' => [
                    [
                        'question_text' => 'What year?',
                        'question_type' => 'multiple_choice',
                        'answers' => [
                            ['answer_text' => '1889', 'is_correct' => true],
                            ['answer_text' => '1903', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'My Quest')
        ->assertJsonPath('data.status', QuestStatus::Draft->value);

    $this->assertDatabaseHas('quests', [
        'title' => 'My Quest',
        'status' => QuestStatus::Draft->value,
        'creator_id' => $this->user->id,
    ]);
    $this->assertDatabaseHas('checkpoints', ['title' => 'First Stop']);
    $this->assertDatabaseHas('questions', ['body' => 'What year?']);
});

it('shows a quest without auth', function () {
    $quest = Quest::factory()->create();

    $response = $this->getJson("/api/v1/quests/{$quest->id}");

    $response->assertOk()
        ->assertJsonPath('data.title', $quest->title);
});

it('updates own quest', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->putJson("/api/v1/quests/{$quest->id}", [
        'title' => 'Updated Title',
        'description' => 'Updated description',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('cannot update another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->putJson("/api/v1/quests/{$quest->id}", [
        'title' => 'Hacked Title',
        'description' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

it('archives own quest instead of deleting', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/quests/{$quest->id}");

    $response->assertOk()
        ->assertJsonPath('message', 'Quest archived successfully.');

    $this->assertDatabaseHas('quests', [
        'id' => $quest->id,
        'status' => QuestStatus::Archived->value,
    ]);
});

it('cannot archive another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/quests/{$quest->id}");

    $response->assertStatus(403);
});

it('publishes public quest as pending_review', function () {
    $quest = Quest::factory()->create([
        'creator_id' => $this->user->id,
        'status' => QuestStatus::Draft,
        'visibility' => QuestVisibility::Public,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.status', QuestStatus::PendingReview->value);

    $quest->refresh();
    expect($quest->status)->toBe(QuestStatus::PendingReview);
});

it('publishes private quest directly', function () {
    $quest = Quest::factory()->create([
        'creator_id' => $this->user->id,
        'status' => QuestStatus::Draft,
        'visibility' => QuestVisibility::Private,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.status', QuestStatus::Published->value);
});

it('cannot publish another users quest', function () {
    $quest = Quest::factory()->create(['status' => QuestStatus::Draft]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/publish");

    $response->assertStatus(403);
});

it('rates another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/rate", [
        'rating' => 5,
        'comment' => 'Great quest!',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('quest_ratings', [
        'quest_id' => $quest->id,
        'user_id' => $this->user->id,
        'rating' => 5,
    ]);
});

it('cannot rate own quest', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/rate", [
        'rating' => 5,
    ]);

    $response->assertStatus(403);
});

it('flags a quest without auth', function () {
    $quest = Quest::factory()->create();

    $response = $this->postJson("/api/v1/quests/{$quest->id}/flag", [
        'reason' => 'Inappropriate content',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('moderation_flags', [
        'flaggable_type' => Quest::class,
        'flaggable_id' => $quest->id,
        'status' => ModerationStatus::Pending->value,
    ]);
});

it('requires authentication for quest creation', function () {
    $response = $this->postJson('/api/v1/quests', [
        'title' => 'Unauthorized',
    ]);

    $response->assertStatus(401);
});

it('validates required fields on quest creation', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/quests', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'category_id', 'difficulty', 'visibility', 'estimated_duration_minutes', 'checkpoints']);
});
