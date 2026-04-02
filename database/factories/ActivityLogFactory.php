<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => ActivityType::QuestCreated,
            'subject_type' => Quest::class,
            'subject_id' => Quest::factory(),
            'metadata' => ['quest_title' => fake()->sentence(3)],
        ];
    }
}
