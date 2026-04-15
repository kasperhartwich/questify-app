<?php

namespace App\Services;

use App\Enums\WrongAnswerBehaviour;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Carbon\Carbon;

class ScoringService
{
    /**
     * Calculate the score awarded for a correct answer.
     *
     * @return array{base: int, speed_bonus: int, total: int}
     */
    public function calculateCorrectAnswerScore(Quest $quest, CheckpointProgress $progress): array
    {
        $base = $quest->scoring_points_per_correct ?? 100;
        $speedBonus = 0;

        if ($quest->scoring_speed_bonus_enabled && $progress->time_taken_seconds !== null) {
            $secondsTaken = $progress->time_taken_seconds;
            $window = config('questify.scoring.speed_bonus_window_seconds', 30);
            $maxBonus = config('questify.scoring.speed_bonus_max_points', 50);
            $speedBonus = (int) floor($maxBonus * max(0, ($window - $secondsTaken) / $window));
        }

        return [
            'base' => $base,
            'speed_bonus' => $speedBonus,
            'total' => $base + $speedBonus,
        ];
    }

    /**
     * Apply a wrong-answer penalty and return the amount deducted.
     */
    public function applyWrongAnswerPenalty(Quest $quest, CheckpointProgress $progress): int
    {
        if (! $quest->scoring_wrong_attempt_penalty_enabled) {
            return 0;
        }

        $penalty = $quest->wrong_answer_penalty_points ?? 0;
        $newScore = max(0, $progress->points_earned - $penalty);
        $actualDeduction = $progress->points_earned - $newScore;

        $progress->update(['points_earned' => $newScore]);

        return $actualDeduction;
    }

    /**
     * Calculate the bonus awarded for completing a quest within the estimated duration.
     */
    public function calculateCompletionBonus(Quest $quest, SessionParticipant $participant, QuestSession $session): int
    {
        if (! $quest->scoring_quest_completion_time_bonus_enabled) {
            return 0;
        }

        if (! $participant->finished_at || ! $session->started_at) {
            return 0;
        }

        $secondsEstimated = $quest->estimated_duration_minutes * 60;

        if ($secondsEstimated <= 0) {
            return 0;
        }

        $secondsTaken = $session->started_at->diffInSeconds($participant->finished_at);

        $maxBonus = config('questify.scoring.completion_bonus_max_points', 200);

        return (int) floor($maxBonus * max(0, ($secondsEstimated - $secondsTaken) / $secondsEstimated));
    }

    /**
     * Handle a wrong answer according to the quest's configured behaviour.
     *
     * @return array<string, mixed>
     */
    public function handleWrongAnswer(Quest $quest, CheckpointProgress $progress, Checkpoint $checkpoint): array
    {
        $progress->increment('wrong_attempts');
        $attempts = $progress->wrong_attempts;

        return match ($quest->wrong_answer_behaviour) {
            WrongAnswerBehaviour::RetryFree => [
                'behaviour' => 'retry_free',
                'attempts' => $attempts,
            ],
            WrongAnswerBehaviour::RetryPenalty => [
                'behaviour' => 'retry_penalty',
                'attempts' => $attempts,
                'penalty' => $this->applyWrongAnswerPenalty($quest, $progress),
            ],
            WrongAnswerBehaviour::Lockout => [
                'behaviour' => 'lockout',
                'attempts' => $attempts,
                'locked_until' => Carbon::now()
                    ->addSeconds($quest->wrong_answer_lockout_seconds ?? 30)
                    ->toIso8601String(),
            ],
            WrongAnswerBehaviour::ThreeStrikesHint => array_merge(
                [
                    'behaviour' => 'three_strikes_hint',
                    'attempts' => $attempts,
                ],
                $attempts >= 3 ? ['hint' => $checkpoint->hint] : [],
            ),
        };
    }
}
