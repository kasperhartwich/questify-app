<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Checkpoint;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'type' => QuestionType::MultipleChoice,
            'body' => fake()->sentence().'?',
            'image_path' => null,
            'hint' => fake()->optional()->sentence(),
            'points' => 10,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate the question is true/false.
     */
    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::TrueFalse,
        ]);
    }
}
