<?php

namespace Database\Factories;

use App\Enums\Difficulty;
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
            'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree,
            'wrong_answer_penalty_points' => 0,
            'wrong_answer_lockout_seconds' => 0,
            'estimated_duration_minutes' => fake()->numberBetween(10, 120),
            'checkpoint_arrival_radius_meters' => 50,
            'scoring_points_per_correct' => 100,
            'scoring_speed_bonus_enabled' => true,
            'scoring_wrong_attempt_penalty_enabled' => false,
            'scoring_quest_completion_time_bonus_enabled' => false,
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
