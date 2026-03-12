<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Category;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quest>
 */
class QuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'cover_image_path' => null,
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'status' => QuestStatus::Draft,
            'visibility' => QuestVisibility::Public,
            'play_mode' => PlayMode::Solo,
            'wrong_answer_behaviour' => WrongAnswerBehaviour::Retry,
            'time_limit_per_question' => fake()->optional()->numberBetween(10, 120),
            'shuffle_questions' => false,
            'shuffle_answers' => false,
            'max_participants' => null,
            'join_code' => null,
            'published_at' => null,
        ];
    }

    /**
     * Indicate the quest is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuestStatus::Published,
            'published_at' => now(),
        ]);
    }
}
