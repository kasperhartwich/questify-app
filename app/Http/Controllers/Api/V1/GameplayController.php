<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Enums\WrongAnswerBehaviour;
use App\Events\CheckpointArrived;
use App\Events\CheckpointCompleted;
use App\Events\LeaderboardUpdated;
use App\Events\QuestCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\ArrivedRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Services\ActivityLogService;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @group Gameplay
 *
 * Real-time gameplay endpoints for active quest sessions.
 */
class GameplayController extends Controller
{
    public function __construct(
        private ScoringService $scoringService,
        private ActivityLogService $activityLogService,
    ) {}

    /**
     * Arrive at checkpoint
     *
     * Record arrival at a checkpoint and receive its questions. Broadcasts CheckpointArrived event.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @bodyParam participant_id integer required The participant ID. Example: 7
     * @bodyParam checkpoint_id integer required The checkpoint ID. Example: 3
     * @bodyParam latitude numeric required The arrival latitude. Example: 55.6763
     * @bodyParam longitude numeric required The arrival longitude. Example: 12.5681
     *
     * @response 200 {"data": {"id": 3, "title": "Norreport Station", "description": "...", "order_index": 2, "questions": [{"id": 11, "question_text": "What year?", "question_type": "multiple_choice", "answers": [{"id": 41, "answer_text": "1917"}]}]}}
     */
    public function arrived(ArrivedRequest $request, string $code): JsonResponse
    {
        $session = $this->getActiveSession($code);

        $participant = SessionParticipant::where('id', $request->validated('participant_id'))
            ->where('quest_session_id', $session->id)
            ->firstOrFail();

        $checkpoint = Checkpoint::where('id', $request->validated('checkpoint_id'))
            ->where('quest_id', $session->quest_id)
            ->firstOrFail();

        broadcast(new CheckpointArrived($session->join_code, $participant, $checkpoint))->toOthers();

        $checkpoint->load('questions.answers');

        return response()->json([
            'data' => [
                'id' => $checkpoint->id,
                'title' => $checkpoint->title,
                'description' => $checkpoint->description,
                'order_index' => $checkpoint->sort_order,
                'questions' => $checkpoint->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question_text' => $question->body,
                    'question_type' => $question->type->value,
                    'image_url' => $question->resolveImageUrl($question->image_path),
                    'answers' => $question->answers->map(fn ($answer) => [
                        'id' => $answer->id,
                        'answer_text' => $answer->body,
                    ]),
                ]),
            ],
        ]);
    }

    /**
     * Answer question
     *
     * Submit an answer to a question. Returns correctness and score.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @bodyParam participant_id integer required The participant ID. Example: 7
     * @bodyParam question_id integer required The question ID. Example: 11
     * @bodyParam answer_id integer optional The selected answer ID (for multiple_choice/true_false). Example: 42
     * @bodyParam answer_text string optional The text answer (for open_text). Example: 1934
     *
     * @response 200 scenario="Correct" {"data": {"correct": true, "score_earned": 115, "speed_bonus": 15, "total_score": 315, "next": "question"}}
     * @response 200 scenario="Incorrect" {"data": {"correct": false, "behaviour": "retry_free", "attempts": 2}}
     */
    public function answer(AnswerQuestionRequest $request, string $code): JsonResponse
    {
        return DB::transaction(function () use ($request, $code): JsonResponse {
            $session = $this->getActiveSession($code);
            $quest = $session->quest;

            $participant = SessionParticipant::lockForUpdate()
                ->where('id', $request->validated('participant_id'))
                ->where('quest_session_id', $session->id)
                ->firstOrFail();

            $question = Question::findOrFail($request->validated('question_id'));
            $checkpoint = $question->checkpoint;

            // Get existing progress for this question
            $existingProgress = CheckpointProgress::lockForUpdate()
                ->where('session_participant_id', $participant->id)
                ->where('question_id', $question->id)
                ->first();

            // Determine correctness
            $isCorrect = $this->evaluateAnswer($question, $request);

            if (! $isCorrect) {
                $wrongAttempts = $existingProgress ? $existingProgress->wrong_attempts + 1 : 1;

                $progress = CheckpointProgress::updateOrCreate(
                    [
                        'session_participant_id' => $participant->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'checkpoint_id' => $checkpoint->id,
                        'answer_id' => $request->validated('answer_id'),
                        'open_ended_answer' => $request->validated('answer_text'),
                        'is_correct' => false,
                        'points_earned' => 0,
                        'wrong_attempts' => $wrongAttempts,
                    ]
                );

                $responseData = [
                    'correct' => false,
                    'behaviour' => $quest->wrong_answer_behaviour->value,
                    'attempts' => $wrongAttempts,
                ];

                match ($quest->wrong_answer_behaviour) {
                    WrongAnswerBehaviour::Lockout => $responseData['locked_until'] = now()
                        ->addSeconds($quest->wrong_answer_lockout_seconds ?? 30)
                        ->toIso8601String(),
                    WrongAnswerBehaviour::ThreeStrikesHint => $wrongAttempts >= 3
                        ? $responseData['hint'] = ($question->hint ?? $checkpoint->hint ?? null)
                        : null,
                    WrongAnswerBehaviour::RetryPenalty => $responseData['penalty'] = $quest->wrong_answer_penalty_points ?? 0,
                    default => null,
                };

                return response()->json(['data' => $responseData]);
            }

            // Calculate score using ScoringService
            $wrongAttempts = $existingProgress ? $existingProgress->wrong_attempts : 0;
            $scoreResult = $this->scoringService->calculateCorrectAnswerScore($quest, $existingProgress ?? new CheckpointProgress([
                'time_taken_seconds' => $request->validated('time_taken_seconds'),
            ]));

            $pointsEarned = $scoreResult['total'];
            $speedBonus = $scoreResult['speed_bonus'];

            // Apply wrong attempt penalty if enabled
            if ($quest->scoring_wrong_attempt_penalty_enabled && $wrongAttempts > 0) {
                $penalty = ($quest->wrong_answer_penalty_points ?? 0) * $wrongAttempts;
                $pointsEarned = max(0, $pointsEarned - $penalty);
            }

            // Record progress
            CheckpointProgress::updateOrCreate(
                [
                    'session_participant_id' => $participant->id,
                    'question_id' => $question->id,
                ],
                [
                    'checkpoint_id' => $checkpoint->id,
                    'answer_id' => $request->validated('answer_id'),
                    'open_ended_answer' => $request->validated('answer_text'),
                    'is_correct' => true,
                    'points_earned' => $pointsEarned,
                    'wrong_attempts' => $wrongAttempts,
                ]
            );

            // Update participant total score
            $totalScore = CheckpointProgress::where('session_participant_id', $participant->id)
                ->where('is_correct', true)
                ->sum('points_earned');
            $participant->update(['score' => $totalScore]);

            // Check if checkpoint is complete
            $checkpointQuestionCount = $checkpoint->questions()->count();
            $answeredCorrectly = CheckpointProgress::where('session_participant_id', $participant->id)
                ->where('checkpoint_id', $checkpoint->id)
                ->where('is_correct', true)
                ->count();

            $checkpointComplete = $answeredCorrectly >= $checkpointQuestionCount;
            $next = $checkpointComplete ? 'checkpoint_complete' : 'question';

            if ($checkpointComplete) {
                $participant->update(['current_checkpoint_index' => $participant->current_checkpoint_index + 1]);
                broadcast(new CheckpointCompleted($session->join_code, $participant, $checkpoint))->toOthers();
            }

            // Check if quest is complete
            $questComplete = $this->isQuestComplete($session, $participant);
            if ($questComplete) {
                $participant->update(['finished_at' => now()]);

                $completionBonus = $this->scoringService->calculateCompletionBonus($quest, $participant->fresh(), $session);
                if ($completionBonus > 0) {
                    $participant->increment('score', $completionBonus);
                }

                broadcast(new QuestCompleted(
                    $session->join_code,
                    $participant->fresh(),
                    $participant->fresh()->score,
                ))->toOthers();

                if ($participant->user_id) {
                    $placement = $session->participants()->whereNotNull('finished_at')->count();
                    $this->activityLogService->log(
                        $participant->user,
                        'quest_completed',
                        $session->quest,
                        [
                            'quest_title' => $session->quest->title,
                            'score' => $participant->fresh()->score,
                            'placement' => $placement,
                            'session_id' => $session->id,
                        ],
                    );
                }
            }

            // Broadcast leaderboard update
            $leaderboard = $this->buildLeaderboard($session);
            broadcast(new LeaderboardUpdated($session->join_code, $leaderboard))->toOthers();

            return response()->json([
                'data' => [
                    'correct' => true,
                    'score_earned' => $pointsEarned,
                    'speed_bonus' => $speedBonus,
                    'total_score' => $participant->fresh()->score,
                    'next' => $next,
                ],
            ]);
        }); // end DB::transaction
    }

    /**
     * Get leaderboard
     *
     * Get the current session leaderboard sorted by score descending. No auth required.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @response 200 {"data": [{"id": 1, "display_name": "Player1", "total_score": 250, "current_checkpoint_index": 2, "quest_completed_at": null}]}
     */
    public function leaderboard(string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)->firstOrFail();

        $participants = $session->participants()
            ->orderByDesc('score')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'display_name' => $p->display_name,
                'total_score' => $p->score,
                'current_checkpoint_index' => $p->current_checkpoint_index,
                'quest_completed_at' => $p->finished_at,
            ]);

        return response()->json(['data' => $participants]);
    }

    private function getActiveSession(string $code): QuestSession
    {
        return QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::Active)
            ->firstOrFail();
    }

    private function evaluateAnswer(Question $question, AnswerQuestionRequest $request): bool
    {
        if ($question->type === QuestionType::OpenText) {
            $answerText = $request->validated('answer_text');
            $correctAnswer = $question->answers()->where('is_correct', true)->first();

            if (! $correctAnswer) {
                return false;
            }

            $normalize = fn (string $text): string => mb_strtolower(
                preg_replace('/\s+/', ' ', trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text)))
            );

            return $normalize($answerText) === $normalize($correctAnswer->body);
        }

        $answerId = $request->validated('answer_id');

        return $question->answers()
            ->where('id', $answerId)
            ->where('is_correct', true)
            ->exists();
    }

    private function isQuestComplete(QuestSession $session, SessionParticipant $participant): bool
    {
        $totalQuestions = $session->quest->checkpoints()
            ->withCount('questions')
            ->get()
            ->sum('questions_count');

        $correctAnswers = CheckpointProgress::where('session_participant_id', $participant->id)
            ->where('is_correct', true)
            ->count();

        return $correctAnswers >= $totalQuestions;
    }

    /** @return Collection<int, array{participant_id: int, display_name: string, score: int, rank: int}> */
    private function buildLeaderboard(QuestSession $session): Collection
    {
        return $session->participants()
            ->orderByDesc('score')
            ->get()
            ->values()
            ->map(fn ($participant, $index) => [
                'participant_id' => $participant->id,
                'display_name' => $participant->display_name,
                'score' => $participant->score,
                'rank' => $index + 1,
            ]);
    }
}
