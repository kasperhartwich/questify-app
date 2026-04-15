<?php

use App\Models\Answer;
use App\Models\Category;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use Database\Seeders\QuestSeeder;

it('seeds quests with checkpoints, questions, and answers', function () {
    $this->seed(QuestSeeder::class);

    expect(Quest::count())->toBe(3);
    expect(Category::count())->toBeGreaterThanOrEqual(3);
    expect(Checkpoint::count())->toBe(9);
    expect(Question::count())->toBe(13);
    expect(Answer::count())->toBeGreaterThanOrEqual(24);

    // Every question has at least one correct answer
    Question::all()->each(function (Question $question) {
        expect($question->answers()->where('is_correct', true)->count())->toBeGreaterThanOrEqual(1);
    });
});

it('is idempotent when run twice', function () {
    $this->seed(QuestSeeder::class);
    $this->seed(QuestSeeder::class);

    expect(Quest::count())->toBe(3);
    expect(Checkpoint::count())->toBe(9);
});
