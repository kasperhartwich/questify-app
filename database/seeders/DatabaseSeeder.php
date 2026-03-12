<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Enums\QuestStatus;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $admin = User::factory()->admin()->create();

        $category = Category::query()->where('slug', 'general-knowledge')->first();

        $quest = Quest::factory()->create([
            'creator_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Welcome to Questify!',
            'description' => 'A sample quest to get you started.',
            'difficulty' => Difficulty::Easy,
            'status' => QuestStatus::Published,
            'published_at' => now(),
        ]);

        $checkpoint = Checkpoint::factory()->create([
            'quest_id' => $quest->id,
            'title' => 'Round 1: Basics',
            'sort_order' => 0,
        ]);

        $question = Question::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'type' => QuestionType::MultipleChoice,
            'body' => 'What is the capital of France?',
            'points' => 10,
            'sort_order' => 0,
        ]);

        Answer::factory()->correct()->create([
            'question_id' => $question->id,
            'body' => 'Paris',
            'sort_order' => 0,
        ]);

        Answer::factory()->create([
            'question_id' => $question->id,
            'body' => 'London',
            'sort_order' => 1,
        ]);

        Answer::factory()->create([
            'question_id' => $question->id,
            'body' => 'Berlin',
            'sort_order' => 2,
        ]);

        Answer::factory()->create([
            'question_id' => $question->id,
            'body' => 'Madrid',
            'sort_order' => 3,
        ]);
    }
}
