<?php

namespace Database\Factories;

use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionParticipant>
 */
class SessionParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quest_session_id' => QuestSession::factory(),
            'user_id' => User::factory(),
            'display_name' => fake()->userName(),
            'score' => 0,
            'finished_at' => null,
        ];
    }
}
