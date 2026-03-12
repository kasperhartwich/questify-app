<?php

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->quest = Quest::factory()->create(['creator_id' => $this->user->id]);
    $this->checkpoint = Checkpoint::factory()->create(['quest_id' => $this->quest->id]);
});

// --- Checkpoint CRUD ---

it('lists checkpoints for own quest', function () {
    Checkpoint::factory()->count(3)->create(['quest_id' => $this->quest->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/quests/{$this->quest->id}/checkpoints");

    $response->assertOk()
        ->assertJsonCount(4, 'data'); // 3 + 1 from beforeEach
});

it('cannot list checkpoints for another users quest', function () {
    $otherQuest = Quest::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/quests/{$otherQuest->id}/checkpoints");

    $response->assertStatus(403);
});

it('creates a checkpoint', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints", [
            'title' => 'New Checkpoint',
            'description' => 'Find the hidden treasure',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('checkpoint.title', 'New Checkpoint');
});

it('auto-increments checkpoint sort_order', function () {
    Checkpoint::factory()->create(['quest_id' => $this->quest->id, 'sort_order' => 0]);
    Checkpoint::factory()->create(['quest_id' => $this->quest->id, 'sort_order' => 1]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints", [
            'title' => 'Auto Sort Checkpoint',
        ]);

    $response->assertStatus(201);
    $checkpoint = Checkpoint::where('title', 'Auto Sort Checkpoint')->first();
    expect($checkpoint->sort_order)->toBe(2);
});

it('shows a checkpoint', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}");

    $response->assertOk()
        ->assertJsonPath('data.title', $this->checkpoint->title);
});

it('updates a checkpoint', function () {
    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}", [
            'title' => 'Updated Checkpoint',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Checkpoint');
});

it('deletes a checkpoint', function () {
    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('checkpoints', ['id' => $this->checkpoint->id]);
});

it('reorders checkpoints', function () {
    $cp1 = Checkpoint::factory()->create(['quest_id' => $this->quest->id, 'sort_order' => 0]);
    $cp2 = Checkpoint::factory()->create(['quest_id' => $this->quest->id, 'sort_order' => 1]);
    $cp3 = Checkpoint::factory()->create(['quest_id' => $this->quest->id, 'sort_order' => 2]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints/reorder", [
            'order' => [$cp3->id, $cp1->id, $cp2->id],
        ]);

    $response->assertOk();
    expect($cp3->fresh()->sort_order)->toBe(0);
    expect($cp1->fresh()->sort_order)->toBe(1);
    expect($cp2->fresh()->sort_order)->toBe(2);
});

// --- Question CRUD ---

it('lists questions for a checkpoint', function () {
    Question::factory()->count(3)->create(['checkpoint_id' => $this->checkpoint->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a question with answers', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions", [
            'type' => QuestionType::MultipleChoice->value,
            'body' => 'What color is the sky?',
            'hint' => 'Look up',
            'points' => 10,
            'answers' => [
                ['body' => 'Blue', 'is_correct' => true],
                ['body' => 'Red', 'is_correct' => false],
                ['body' => 'Green', 'is_correct' => false],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('question.body', 'What color is the sky?');

    $question = Question::where('body', 'What color is the sky?')->first();
    expect($question->answers()->count())->toBe(3);
    expect($question->correctAnswer()->count())->toBe(1);
});

it('creates a true/false question', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions", [
            'type' => QuestionType::TrueFalse->value,
            'body' => 'The earth is flat',
            'points' => 5,
            'answers' => [
                ['body' => 'True', 'is_correct' => false],
                ['body' => 'False', 'is_correct' => true],
            ],
        ]);

    $response->assertStatus(201);
});

it('creates an open-ended question', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions", [
            'type' => QuestionType::OpenEnded->value,
            'body' => 'Describe what you see',
            'points' => 15,
        ]);

    $response->assertStatus(201);
});

it('shows a question', function () {
    $question = Question::factory()->create(['checkpoint_id' => $this->checkpoint->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions/{$question->id}");

    $response->assertOk()
        ->assertJsonPath('data.body', $question->body);
});

it('updates a question', function () {
    $question = Question::factory()->create(['checkpoint_id' => $this->checkpoint->id]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions/{$question->id}", [
            'body' => 'Updated question body',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.body', 'Updated question body');
});

it('updates a question with inline answer management', function () {
    $question = Question::factory()->create(['checkpoint_id' => $this->checkpoint->id]);
    $answer1 = Answer::factory()->create(['question_id' => $question->id, 'body' => 'Old Answer']);
    $answer2 = Answer::factory()->create(['question_id' => $question->id, 'body' => 'To Delete']);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions/{$question->id}", [
            'answers' => [
                ['id' => $answer1->id, 'body' => 'Updated Answer', 'is_correct' => true],
                ['body' => 'New Answer', 'is_correct' => false],
            ],
        ]);

    $response->assertOk();
    expect($question->answers()->count())->toBe(2);
    expect($answer1->fresh()->body)->toBe('Updated Answer');
    $this->assertDatabaseMissing('answers', ['id' => $answer2->id]);
});

it('deletes a question', function () {
    $question = Question::factory()->create(['checkpoint_id' => $this->checkpoint->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/quests/{$this->quest->id}/checkpoints/{$this->checkpoint->id}/questions/{$question->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('questions', ['id' => $question->id]);
});

it('cannot manage questions on another users quest', function () {
    $otherQuest = Quest::factory()->create();
    $otherCheckpoint = Checkpoint::factory()->create(['quest_id' => $otherQuest->id]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/quests/{$otherQuest->id}/checkpoints/{$otherCheckpoint->id}/questions", [
            'type' => QuestionType::MultipleChoice->value,
            'body' => 'Unauthorized question',
            'points' => 10,
        ]);

    $response->assertStatus(403);
});
