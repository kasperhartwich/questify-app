<?php

use App\Enums\QuestionType;
use App\Events\CheckpointCompleted;
use App\Events\LeaderboardUpdated;
use App\Events\QuestCompleted as QuestCompletedEvent;
use App\Models\Checkpoint;
use App\Models\CheckpointProgress;
use App\Models\Question;
use App\Models\QuestSession;
use App\Models\SessionParticipant;
use App\Services\ScoringService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Question')]
class extends Component
{
    public QuestSession $session;

    public SessionParticipant $participant;

    public Checkpoint $checkpoint;

    public ?Question $currentQuestion = null;

    public int $currentQuestionIndex = 0;

    public int $totalQuestions = 0;

    public ?int $selectedAnswerId = null;

    public string $openEndedAnswer = '';

    public ?bool $lastAnswerCorrect = null;

    public ?int $lastPointsEarned = null;

    public bool $showFeedback = false;

    public int $startedAt = 0;

    public function mount(string $code, int $checkpoint): void
    {
        $this->session = QuestSession::where('join_code', $code)
            ->with('quest')
            ->firstOrFail();

        $this->participant = $this->session->participants()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->checkpoint = Checkpoint::with('questions.answers')->findOrFail($checkpoint);
        $this->totalQuestions = $this->checkpoint->questions->count();
        $this->startedAt = now()->timestamp;
        $this->determineCurrentQuestion();
    }

    public function determineCurrentQuestion(): void
    {
        $answeredQuestionIds = $this->participant->checkpointProgress()
            ->where('checkpoint_id', $this->checkpoint->id)
            ->where('is_correct', true)
            ->pluck('question_id')
            ->toArray();

        $questions = $this->checkpoint->questions;

        foreach ($questions as $index => $question) {
            if (! in_array($question->id, $answeredQuestionIds)) {
                $this->currentQuestion = $question;
                $this->currentQuestionIndex = $index;
                $this->selectedAnswerId = null;
                $this->openEndedAnswer = '';
                $this->showFeedback = false;
                $this->startedAt = now()->timestamp;

                return;
            }
        }

        $this->handleCheckpointComplete();
    }

    public function submitAnswer(): void
    {
        if (! $this->currentQuestion) {
            return;
        }

        $timeTaken = now()->timestamp - $this->startedAt;

        $isCorrect = false;
        $answerId = null;

        if ($this->currentQuestion->type === QuestionType::OpenEnded) {
            $isCorrect = true;
            $this->openEndedAnswer = trim($this->openEndedAnswer);
        } else {
            if (! $this->selectedAnswerId) {
                return;
            }

            $answer = $this->currentQuestion->answers->firstWhere('id', $this->selectedAnswerId);
            $isCorrect = $answer?->is_correct ?? false;
            $answerId = $this->selectedAnswerId;
        }

        $scoringService = app(ScoringService::class);

        $existingProgress = CheckpointProgress::where('session_participant_id', $this->participant->id)
            ->where('question_id', $this->currentQuestion->id)
            ->first();

        $wrongAttempts = $existingProgress?->wrong_attempts ?? 0;

        if (! $isCorrect) {
            $wrongAttempts++;
            CheckpointProgress::updateOrCreate(
                [
                    'session_participant_id' => $this->participant->id,
                    'question_id' => $this->currentQuestion->id,
                ],
                [
                    'checkpoint_id' => $this->checkpoint->id,
                    'answer_id' => $answerId,
                    'is_correct' => false,
                    'points_earned' => 0,
                    'time_taken_seconds' => $timeTaken,
                    'wrong_attempts' => $wrongAttempts,
                ]
            );

            $wrongResult = $scoringService->handleWrongAnswer($this->session->quest, $this->participant, $this->currentQuestion);
            $this->lastAnswerCorrect = false;
            $this->lastPointsEarned = 0;
            $this->showFeedback = true;

            if ($wrongResult['locked_out']) {
                $this->dispatch('answer-feedback', correct: false, lockedOut: true);
            }

            return;
        }

        $points = $scoringService->calculateAnswerScore(
            $this->currentQuestion,
            $timeTaken,
            true,
            $wrongAttempts
        );

        CheckpointProgress::updateOrCreate(
            [
                'session_participant_id' => $this->participant->id,
                'question_id' => $this->currentQuestion->id,
            ],
            [
                'checkpoint_id' => $this->checkpoint->id,
                'answer_id' => $answerId,
                'open_ended_answer' => $this->currentQuestion->type === QuestionType::OpenEnded ? $this->openEndedAnswer : null,
                'is_correct' => true,
                'points_earned' => $points,
                'time_taken_seconds' => $timeTaken,
                'wrong_attempts' => $wrongAttempts,
            ]
        );

        $this->participant->increment('score', $points);
        $this->lastAnswerCorrect = true;
        $this->lastPointsEarned = $points;
        $this->showFeedback = true;
    }

    public function nextQuestion(): void
    {
        $this->determineCurrentQuestion();
    }

    private function handleCheckpointComplete(): void
    {
        $sessionCode = $this->session->join_code;

        broadcast(new CheckpointCompleted(
            $sessionCode,
            $this->participant->fresh(),
            $this->checkpoint
        ))->toOthers();

        $leaderboard = $this->session->participants()
            ->orderByDesc('score')
            ->get()
            ->map(fn ($p, $i) => [
                'participant_id' => $p->id,
                'display_name' => $p->display_name,
                'score' => $p->score,
                'rank' => $i + 1,
            ]);

        broadcast(new LeaderboardUpdated($sessionCode, $leaderboard));

        $totalCheckpoints = $this->session->quest->checkpoints->count();
        $completedCheckpoints = $this->participant->checkpointProgress()
            ->where('is_correct', true)
            ->distinct('checkpoint_id')
            ->count('checkpoint_id');

        $totalQuestions = $this->session->quest->checkpoints->sum(fn ($cp) => $cp->questions->count());
        $answeredQuestions = $this->participant->checkpointProgress()
            ->where('is_correct', true)
            ->count();

        if ($answeredQuestions >= $totalQuestions) {
            $this->participant->update(['finished_at' => now()]);

            $scoringService = app(ScoringService::class);
            $completionBonus = $scoringService->calculateCompletionBonus($this->participant->fresh(), $this->session->quest);
            $this->participant->increment('score', $completionBonus);

            broadcast(new QuestCompletedEvent(
                $sessionCode,
                $this->participant->fresh(),
                $this->participant->fresh()->score
            ));

            $this->redirect('/session/' . $sessionCode . '/complete');

            return;
        }

        $this->redirect('/session/' . $sessionCode . '/play');
    }
};
?>

<div class="flex flex-col">
    {{-- Progress Bar --}}
    <div class="bg-white px-4 py-3 dark:bg-gray-800">
        <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $checkpoint->title }}</span>
            <span>{{ $currentQuestionIndex + 1 }}/{{ $totalQuestions }}</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $totalQuestions > 0 ? (($currentQuestionIndex + ($showFeedback && $lastAnswerCorrect ? 1 : 0)) / $totalQuestions) * 100 : 0 }}%"></div>
        </div>
    </div>

    @if ($currentQuestion)
        <div class="flex-1 space-y-4 p-4">
            {{-- Question Body --}}
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <span class="mb-2 inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                    {{ str_replace('_', ' ', ucfirst($currentQuestion->type->value)) }}
                </span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $currentQuestion->body }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $currentQuestion->points }} {{ __('quests.points') }}</p>
            </div>

            {{-- Answer Options --}}
            @if (!$showFeedback)
                @if ($currentQuestion->type === \App\Enums\QuestionType::OpenEnded)
                    <div>
                        <textarea
                            wire:model="openEndedAnswer"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="{{ __('sessions.type_answer') }}"
                        ></textarea>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($currentQuestion->answers as $answer)
                            <button
                                wire:click="$set('selectedAnswerId', {{ $answer->id }})"
                                class="w-full rounded-xl border-2 px-4 py-3 text-left text-sm font-medium transition-colors
                                    {{ $selectedAnswerId === $answer->id
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-900 dark:border-indigo-400 dark:bg-indigo-900/30 dark:text-indigo-300'
                                        : 'border-gray-200 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white' }}"
                                wire:key="answer-{{ $answer->id }}"
                            >
                                {{ $answer->body }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <button
                    wire:click="submitAnswer"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    {{ ($currentQuestion->type !== \App\Enums\QuestionType::OpenEnded && !$selectedAnswerId) ? 'disabled' : '' }}
                >
                    {{ __('sessions.submit_answer') }}
                </button>
            @endif

            {{-- Feedback --}}
            @if ($showFeedback)
                <div class="rounded-xl p-4 text-center {{ $lastAnswerCorrect ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <p class="text-3xl">{{ $lastAnswerCorrect ? '✅' : '❌' }}</p>
                    <p class="mt-2 text-lg font-bold {{ $lastAnswerCorrect ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                        {{ $lastAnswerCorrect ? __('sessions.correct') : __('sessions.wrong') }}
                    </p>
                    @if ($lastAnswerCorrect && $lastPointsEarned)
                        <p class="text-sm text-green-600 dark:text-green-400">+{{ $lastPointsEarned }} {{ __('quests.points') }}</p>
                    @endif
                </div>

                <button
                    wire:click="nextQuestion"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700"
                >
                    {{ __('general.next') }}
                </button>
            @endif
        </div>
    @endif
</div>
