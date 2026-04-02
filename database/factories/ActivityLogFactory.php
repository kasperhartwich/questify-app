<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\ActivityType;
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
            'activity_type_id' => ActivityType::firstOrCreate(
                ['key' => 'quest_created'],
                ['name' => 'Created new quest', 'icon' => 'pencil', 'show_in_app' => true],
            )->id,
            'subject_type' => Quest::class,
            'subject_id' => Quest::factory(),
            'metadata' => ['quest_title' => fake()->sentence(3)],
        ];
    }
}
