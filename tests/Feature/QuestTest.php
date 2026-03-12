<?php

use App\Enums\Difficulty;
use App\Enums\ModerationStatus;
use App\Enums\PlayMode;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Category;
use App\Models\Quest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
});

it('lists published public quests', function () {
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

    $response = $this->actingAs($this->user)->getJson('/api/v1/quests');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a quest as draft', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/quests', [
        'category_id' => $this->category->id,
        'title' => 'My Quest',
        'description' => 'A test quest',
        'difficulty' => Difficulty::Medium->value,
        'visibility' => QuestVisibility::Public->value,
        'play_mode' => PlayMode::Solo->value,
        'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree->value,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('quest.title', 'My Quest')
        ->assertJsonPath('quest.status', QuestStatus::Draft->value);

    $this->assertDatabaseHas('quests', [
        'title' => 'My Quest',
        'status' => QuestStatus::Draft->value,
        'creator_id' => $this->user->id,
    ]);
});

it('shows a quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->getJson("/api/v1/quests/{$quest->id}");

    $response->assertOk()
        ->assertJsonPath('data.title', $quest->title);
});

it('updates own quest', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->patchJson("/api/v1/quests/{$quest->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('cannot update another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->patchJson("/api/v1/quests/{$quest->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
});

it('deletes own quest', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/quests/{$quest->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('quests', ['id' => $quest->id]);
});

it('cannot delete another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/quests/{$quest->id}");

    $response->assertStatus(403);
});

it('publishes own quest', function () {
    $quest = Quest::factory()->create([
        'creator_id' => $this->user->id,
        'status' => QuestStatus::Draft,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.status', QuestStatus::Published->value);

    $quest->refresh();
    expect($quest->status)->toBe(QuestStatus::Published);
    expect($quest->published_at)->not->toBeNull();
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
        'review' => 'Great quest!',
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

it('updates existing rating on re-rate', function () {
    $quest = Quest::factory()->create();

    $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/rate", [
        'rating' => 3,
    ]);

    $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/rate", [
        'rating' => 5,
        'review' => 'Changed my mind!',
    ]);

    expect($quest->ratings()->where('user_id', $this->user->id)->count())->toBe(1);
    expect($quest->ratings()->where('user_id', $this->user->id)->first()->rating)->toBe(5);
});

it('flags another users quest', function () {
    $quest = Quest::factory()->create();

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/flag", [
        'reason' => 'Inappropriate content',
        'description' => 'Contains offensive language',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('moderation_flags', [
        'flaggable_type' => Quest::class,
        'flaggable_id' => $quest->id,
        'reporter_id' => $this->user->id,
        'status' => ModerationStatus::Pending->value,
    ]);
});

it('cannot flag own quest', function () {
    $quest = Quest::factory()->create(['creator_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/quests/{$quest->id}/flag", [
        'reason' => 'Test',
    ]);

    $response->assertStatus(403);
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
        ->assertJsonValidationErrors(['category_id', 'title', 'difficulty', 'visibility', 'play_mode', 'wrong_answer_behaviour']);
});
