<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\Quest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkpoint>
 */
class CheckpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quest_id' => Quest::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'hint' => null,
            'sort_order' => 0,
        ];
    }
}
