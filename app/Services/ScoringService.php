<?php

namespace App\Services;

use App\Enums\WrongAnswerBehaviour;
use App\Models\CheckpointProgress;
use App\Models\Quest;
use App\Models\Question;
use App\Models\SessionParticipant;

class ScoringService
{
    private const MAX_SPEED_BONUS = 50;

    private const SPEED_BONUS_WINDOW_SECONDS = 30;

    private const MAX_COMPLETION_BONUS = 200;

    private const RETRY_PENALTY_PER_ATTEMPT = 10;

    private const THREE_STRIKES_LIMIT = 3;

    /**
     * Calculate score for an answer: base points + speed bonus (linear 50pts at 0s to 0pts at 30s).
     */
    public function calculateAnswerScore(Question $question, int $timeTakenSeconds, bool $isCorrect, int $wrongAttempts = 0): int
    {
        if (! $isCorrect) {
            return 0;
        }

        $basePoints = $question->points;

        $clampedTime = max(0, min(self::SPEED_BONUS_WINDOW_SECONDS, $timeTakenSeconds));
        $speedBonus = (int) round(
            self::MAX_SPEED_BONUS * (1 - $clampedTime / self::SPEED_BONUS_WINDOW_SECONDS)
        );

        $penalty = $wrongAttempts * self::RETRY_PENALTY_PER_ATTEMPT;

        return max(0, $basePoints + $speedBonus - $penalty);
    }

    /**
     * Calculate completion bonus: up to 200pts with linear decay based on time taken.
     */
    public function calculateCompletionBonus(SessionParticipant $participant, Quest $quest): int
    {
        $session = $participant->questSession;

        if (! $session->started_at || ! $participant->finished_at) {
            return 0;
        }

        $totalSeconds = $session->started_at->diffInSeconds($participant->finished_at);

        $timePerQuestion = $quest->time_limit_per_question ?? self::SPEED_BONUS_WINDOW_SECONDS;
        $totalQuestions = $quest->checkpoints->sum(fn ($checkpoint) => $checkpoint->questions->count());
        $maxTime = $timePerQuestion * $totalQuestions;

        if ($maxTime <= 0) {
            return self::MAX_COMPLETION_BONUS;
        }

        $ratio = min(1.0, $totalSeconds / $maxTime);

        return (int) round(self::MAX_COMPLETION_BONUS * (1 - $ratio));
    }

    /**
     * Handle a wrong answer based on quest configuration.
     *
     * @return array{can_retry: bool, penalty: int, locked_out: bool, hint: ?string}
     */
    public function handleWrongAnswer(Quest $quest, SessionParticipant $participant, Question $question): array
    {
        $wrongAttempts = CheckpointProgress::where('session_participant_id', $participant->id)
            ->where('question_id', $question->id)
            ->value('wrong_attempts') ?? 0;

        return match ($quest->wrong_answer_behaviour) {
            WrongAnswerBehaviour::RetryFree => [
                'can_retry' => true,
                'penalty' => 0,
                'locked_out' => false,
                'hint' => null,
            ],
            WrongAnswerBehaviour::RetryPenalty => [
                'can_retry' => true,
                'penalty' => self::RETRY_PENALTY_PER_ATTEMPT * $wrongAttempts,
                'locked_out' => false,
                'hint' => null,
            ],
            WrongAnswerBehaviour::Lockout => [
                'can_retry' => false,
                'penalty' => 0,
                'locked_out' => true,
                'hint' => null,
            ],
            WrongAnswerBehaviour::ThreeStrikesHint => [
                'can_retry' => $wrongAttempts < self::THREE_STRIKES_LIMIT,
                'penalty' => 0,
                'locked_out' => $wrongAttempts >= self::THREE_STRIKES_LIMIT,
                'hint' => $wrongAttempts >= (self::THREE_STRIKES_LIMIT - 1) ? $question->hint : null,
            ],
        };
    }
}
