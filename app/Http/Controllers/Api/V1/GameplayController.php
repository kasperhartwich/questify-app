<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Events\CheckpointArrived;
use App\Events\CheckpointCompleted;
use App\Events\LeaderboardUpdated;
use App\Events\QuestCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Resources\CheckpointResource;
use App\Http\Resources\LeaderboardEntryResource;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GameplayController extends Controller
{
    public function __construct(
        private ScoringService $scoringService,
    ) {}

    public function arrived(Request $request, string $code, Checkpoint $checkpoint): JsonResponse
    {
        $session = $this->getActiveSession($code);
        $participant = $this->getParticipant($session, $request->user());

        broadcast(new CheckpointArrived($session->join_code, $participant, $checkpoint))->toOthers();

        // Return only this checkpoint's questions (not future checkpoints)
        $checkpoint->load('questions.answers');

        return response()->json([
            'checkpoint' => new CheckpointResource($checkpoint),
        ]);
    }

    public function answer(AnswerQuestionRequest $request, string $code, Checkpoint $checkpoint, Question $question): JsonResponse
    {
        $session = $this->getActiveSession($code);
        $participant = $this->getParticipant($session, $request->user());
        $quest = $session->quest;

        // Check if already correctly answered
        $existingProgress = CheckpointProgress::where('session_participant_id', $participant->id)
            ->where('question_id', $question->id)
            ->first();

        if ($existingProgress && $existingProgress->is_correct) {
            return response()->json([
                'message' => __('sessions.already_answered'),
                'points_earned' => $existingProgress->points_earned,
            ]);
        }

        // Determine correctness
        $isCorrect = $this->evaluateAnswer($question, $request);

        if (! $isCorrect) {
            // Track wrong attempt
            $wrongAttempts = $existingProgress ? $existingProgress->wrong_attempts + 1 : 1;

            CheckpointProgress::updateOrCreate(
                [
                    'session_participant_id' => $participant->id,
                    'question_id' => $question->id,
                ],
                [
                    'checkpoint_id' => $checkpoint->id,
                    'answer_id' => $request->validated('answer_id'),
                    'open_ended_answer' => $request->validated('open_ended_answer'),
                    'is_correct' => false,
                    'points_earned' => 0,
                    'time_taken_seconds' => $request->validated('time_taken_seconds'),
                    'wrong_attempts' => $wrongAttempts,
                ]
            );

            $wrongAnswerResult = $this->scoringService->handleWrongAnswer($quest, $participant, $question);

            return response()->json([
                'is_correct' => false,
                'points_earned' => 0,
                'wrong_answer' => $wrongAnswerResult,
            ]);
        }

        // Calculate score
        $wrongAttempts = $existingProgress ? $existingProgress->wrong_attempts : 0;
        $pointsEarned = $this->scoringService->calculateAnswerScore(
            $question,
            $request->validated('time_taken_seconds'),
            true,
            $wrongAttempts,
        );

        // Record progress
        CheckpointProgress::updateOrCreate(
            [
                'session_participant_id' => $participant->id,
                'question_id' => $question->id,
            ],
            [
                'checkpoint_id' => $checkpoint->id,
                'answer_id' => $request->validated('answer_id'),
                'open_ended_answer' => $request->validated('open_ended_answer'),
                'is_correct' => true,
                'points_earned' => $pointsEarned,
                'time_taken_seconds' => $request->validated('time_taken_seconds'),
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

        if ($checkpointComplete) {
            broadcast(new CheckpointCompleted($session->join_code, $participant, $checkpoint))->toOthers();
        }

        // Broadcast leaderboard update
        $leaderboard = $this->buildLeaderboard($session);
        broadcast(new LeaderboardUpdated($session->join_code, $leaderboard))->toOthers();

        // Check if quest is complete (all checkpoints done)
        $questComplete = $this->isQuestComplete($session, $participant);

        if ($questComplete) {
            $participant->update(['finished_at' => now()]);

            // Calculate completion bonus
            $quest->load('checkpoints.questions');
            $completionBonus = $this->scoringService->calculateCompletionBonus($participant->fresh(), $quest);
            $participant->increment('score', $completionBonus);

            broadcast(new QuestCompleted(
                $session->join_code,
                $participant->fresh(),
                $participant->fresh()->score,
            ))->toOthers();

            // Refresh leaderboard after bonus
            $leaderboard = $this->buildLeaderboard($session);
            broadcast(new LeaderboardUpdated($session->join_code, $leaderboard))->toOthers();
        }

        return response()->json([
            'is_correct' => true,
            'points_earned' => $pointsEarned,
            'checkpoint_complete' => $checkpointComplete,
            'quest_complete' => $questComplete,
        ]);
    }

    public function leaderboard(Request $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)->firstOrFail();

        $participants = $session->participants()
            ->orderByDesc('score')
            ->get();

        return response()->json([
            'leaderboard' => LeaderboardEntryResource::collection($participants),
        ]);
    }

    private function getActiveSession(string $code): QuestSession
    {
        return QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::InProgress)
            ->firstOrFail();
    }

    private function getParticipant(QuestSession $session, $user): SessionParticipant
    {
        return $session->participants()
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    private function evaluateAnswer(Question $question, AnswerQuestionRequest $request): bool
    {
        if ($question->type === QuestionType::OpenEnded) {
            // Open-ended questions are always marked as correct (manual review later)
            return true;
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
