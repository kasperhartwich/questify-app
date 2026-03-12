<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Question;
use App\Models\SessionParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckpointProgress>
 */
class CheckpointProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_participant_id' => SessionParticipant::factory(),
            'checkpoint_id' => Checkpoint::factory(),
            'question_id' => Question::factory(),
            'answer_id' => Answer::factory(),
            'open_ended_answer' => null,
            'is_correct' => fake()->boolean(),
            'points_earned' => fake()->numberBetween(0, 10),
            'time_taken_seconds' => fake()->numberBetween(1, 120),
        ];
    }
}
