<?php

namespace Database\Factories;

use App\Enums\PlayMode;
use App\Enums\SessionStatus;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuestSession>
 */
class QuestSessionFactory extends Factory
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
            'host_id' => User::factory(),
            'status' => SessionStatus::Waiting,
            'join_code' => strtoupper(Str::random(6)),
            'play_mode' => fake()->randomElement(PlayMode::cases()),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
