<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\ModerationFlag;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationFlag>
 */
class ModerationFlagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flaggable_type' => Quest::class,
            'flaggable_id' => Quest::factory(),
            'reporter_id' => User::factory(),
            'moderator_id' => null,
            'reason' => fake()->randomElement(['spam', 'inappropriate', 'copyright', 'other']),
            'description' => fake()->optional()->sentence(),
            'status' => ModerationStatus::Pending,
            'resolution_note' => null,
            'resolved_at' => null,
        ];
    }
}
