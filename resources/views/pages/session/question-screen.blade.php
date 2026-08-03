<?php

use App\Enums\QuestionType;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Question')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $code = '';

    public int $participantId = 0;

    public array $checkpointData = [];

    public array $questions = [];

    public int $currentQuestionIndex = 0;

    public int $totalQuestions = 0;

    public ?int $selectedAnswerId = null;

    public string $openEndedAnswer = '';

    public ?bool $lastAnswerCorrect = null;

    public ?int $lastPointsEarned = null;

    public bool $showFeedback = false;

    /** Wrong-answer behaviour returned by the API for the last incorrect attempt. */
    public ?string $wrongBehaviour = null;

    /** Points deducted on the last incorrect attempt (retry_penalty). */
    public ?int $penaltyPoints = null;

    /** Hint revealed for the current question (three_strikes_hint). Persists until the question is passed. */
    public ?string $revealedHint = null;

    /** Seconds remaining on a lockout after an incorrect attempt (lockout). */
    public int $lockoutRemaining = 0;

    public array $answeredQuestionIds = [];

    public bool $questComplete = false;

    public bool $checkpointComplete = false;

    public function mount(string $code, int $checkpoint): void
    {
        $this->code = $code;
        $this->participantId = session('questify_participant_id', 0);

        // Fetch checkpoint data via arrived endpoint (which returns questions)
        $response = $this->tryApiCall(fn () => $this->api->gameplay()->arrived(
            $this->code,
            $this->participantId,
            $checkpoint,
            0, // latitude placeholder
            0, // longitude placeholder
        ));

        if ($response) {
            $this->checkpointData = $response['data'] ?? [];
            $questions = $this->checkpointData['questions'] ?? [];

            // Shuffle multiple-choice answers ONCE, here at mount, so the order stays
            // stable across re-renders instead of re-shuffling on every interaction.
            foreach ($questions as $i => $question) {
                $type = QuestionType::tryFrom($question['question_type'] ?? '');
                if ($type !== QuestionType::TrueFalse && ! empty($question['answers'])) {
                    $questions[$i]['answers'] = collect($question['answers'])->shuffle()->values()->all();
                }
            }

            $this->questions = $questions;
            $this->totalQuestions = count($this->questions);
        }
    }

    public function getCurrentQuestionProperty(): ?object
    {
        foreach ($this->questions as $index => $question) {
            if (! in_array($question['id'], $this->answeredQuestionIds)) {
                $this->currentQuestionIndex = $index;

                $obj = (object) $question;
                $obj->type = QuestionType::tryFrom($question['question_type'] ?? '') ?? QuestionType::MultipleChoice;
                $obj->body = $question['question_text'] ?? $question['body'] ?? '';
                $obj->points = $question['points'] ?? 10;
                $obj->answers = collect($question['answers'] ?? [])->map(fn ($a) => (object) [
                    'id' => $a['id'],
                    'body' => $a['answer_text'] ?? $a['body'] ?? '',
                ]);

                return $obj;
            }
        }

        return null;
    }

    public function submitAnswer(): void
    {
        $currentQuestion = $this->currentQuestion;
        if (! $currentQuestion) {
            return;
        }

        // Guard against double submission (spec 8): while feedback is shown the player must
        // either advance (correct) or tap "Try again" (incorrect) before re-submitting.
        if ($this->showFeedback) {
            return;
        }

        $answerId = null;
        $answerText = null;

        if ($currentQuestion->type === QuestionType::OpenText) {
            $answerText = trim($this->openEndedAnswer);
        } else {
            if (! $this->selectedAnswerId) {
                return;
            }
            $answerId = $this->selectedAnswerId;
        }

        $response = $this->tryApiCall(fn () => $this->api->gameplay()->answer(
            $this->code,
            $this->participantId,
            $currentQuestion->id,
            $answerId,
            $answerText,
        ));

        if (! $response) {
            return;
        }

        $data = $response['data'] ?? [];

        $this->lastAnswerCorrect = $data['correct'] ?? false;
        $this->showFeedback = true;

        if (! $this->lastAnswerCorrect) {
            $this->applyWrongAnswer($data);

            return;
        }

        $this->lastPointsEarned = $data['score_earned'] ?? 0;
        $this->answeredQuestionIds[] = $currentQuestion->id;
        $this->wrongBehaviour = null;
        $this->penaltyPoints = null;
        $this->revealedHint = null;
        $this->lockoutRemaining = 0;

        $next = $data['next'] ?? 'question';
        if ($next === 'quest_complete') {
            $this->questComplete = true;
            $this->checkpointComplete = true;
        } elseif ($next === 'checkpoint_complete') {
            $this->checkpointComplete = true;
        }
    }

    /**
     * Apply the wrong-answer behaviour returned by the API (spec 5.15).
     *
     * @param  array<string, mixed>  $data
     */
    private function applyWrongAnswer(array $data): void
    {
        $this->wrongBehaviour = $data['behaviour'] ?? 'retry_free';
        $this->penaltyPoints = null;
        $this->lockoutRemaining = 0;

        match ($this->wrongBehaviour) {
            'retry_penalty' => $this->penaltyPoints = (int) ($data['penalty'] ?? 0),
            'lockout' => $this->lockoutRemaining = $this->secondsUntil($data['locked_until'] ?? null),
            'three_strikes_hint' => filled($data['hint'] ?? null) ? $this->revealedHint = $data['hint'] : null,
            default => null,
        };
    }

    private function secondsUntil(?string $iso): int
    {
        if (! $iso) {
            return 0;
        }

        return max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($iso), false));
    }

    /**
     * Retry the current question after an incorrect attempt (retry_free / retry_penalty /
     * three_strikes_hint, and lockout once the countdown has elapsed).
     */
    public function tryAgain(): void
    {
        $this->selectedAnswerId = null;
        $this->openEndedAnswer = '';
        $this->showFeedback = false;
        $this->wrongBehaviour = null;
        $this->penaltyPoints = null;
        $this->lockoutRemaining = 0;
        // $revealedHint is intentionally kept so the hint stays visible while retrying.
    }

    public function nextQuestion(): void
    {
        $this->selectedAnswerId = null;
        $this->openEndedAnswer = '';
        $this->showFeedback = false;
        $this->wrongBehaviour = null;
        $this->penaltyPoints = null;
        $this->revealedHint = null;
        $this->lockoutRemaining = 0;

        if ($this->currentQuestion === null || $this->checkpointComplete) {
            if ($this->questComplete) {
                $this->redirect('/session/' . $this->code . '/complete');
            } else {
                $currentIndex = session('questify_checkpoint_index', 0);
                session()->put('questify_checkpoint_index', $currentIndex + 1);
                $this->redirect('/session/' . $this->code . '/play');
            }
        }
    }
};
?>

<div class="flex flex-col">
    {{-- Progress Bar --}}
    <div class="bg-white px-4 py-3 dark:bg-gray-800">
        <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $checkpointData['title'] ?? '' }}</span>
            <span>{{ $currentQuestionIndex + 1 }}/{{ $totalQuestions }}</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="h-full rounded-full bg-forest-600 transition-all" style="width: {{ $totalQuestions > 0 ? (($currentQuestionIndex + ($showFeedback && $lastAnswerCorrect ? 1 : 0)) / $totalQuestions) * 100 : 0 }}%"></div>
        </div>
    </div>

    @if ($this->currentQuestion)
        <div class="flex-1 space-y-4 p-4">
            {{-- Question Body --}}
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <span class="mb-2 inline-block rounded-full bg-forest-100 px-2 py-0.5 text-xs font-medium text-forest-700 dark:bg-forest-900/30 dark:text-forest-400">
                    {{ str_replace('_', ' ', ucfirst($this->currentQuestion->type->value)) }}
                </span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->currentQuestion->body }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $this->currentQuestion->points }} {{ __('quests.points') }}</p>
            </div>

            {{-- Revealed hint (three_strikes_hint) — stays visible while retrying --}}
            @if ($revealedHint)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/40 dark:bg-amber-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ __('sessions.hint_label') }}</p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">{{ $revealedHint }}</p>
                </div>
            @endif

            {{-- Answer Options --}}
            @if (!$showFeedback)
                @if ($this->currentQuestion->type === \App\Enums\QuestionType::OpenText)
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
                        @foreach ($this->currentQuestion->answers as $answer)
                            <button
                                wire:click="$set('selectedAnswerId', {{ $answer->id }})"
                                class="w-full rounded-xl border-2 px-4 py-3 text-left text-sm font-medium transition-colors
                                    {{ $selectedAnswerId === $answer->id
                                        ? 'border-forest-600 bg-forest-50 text-forest-900 dark:border-forest-400 dark:bg-forest-900/30 dark:text-forest-300'
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
                    wire:loading.attr="disabled"
                    wire:target="submitAnswer"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500 disabled:opacity-50"
                    {{ ($this->currentQuestion->type !== \App\Enums\QuestionType::OpenText && !$selectedAnswerId) ? 'disabled' : '' }}
                >
                    {{ __('sessions.submit_answer') }}
                </button>
            @endif

            {{-- Feedback --}}
            @if ($showFeedback)
                @if ($lastAnswerCorrect)
                    <div class="rounded-xl bg-green-50 p-4 text-center dark:bg-green-900/20">
                        <p class="text-3xl">✅</p>
                        <p class="mt-2 text-lg font-bold text-green-700 dark:text-green-400">{{ __('sessions.correct') }}</p>
                        @if ($lastPointsEarned)
                            <p class="text-sm text-green-600 dark:text-green-400">+{{ $lastPointsEarned }} {{ __('quests.points') }}</p>
                        @endif
                    </div>

                    <button
                        wire:click="nextQuestion"
                        class="w-full rounded-xl bg-forest-600 px-4 py-3.5 font-heading text-sm font-bold text-white hover:bg-forest-700"
                    >
                        {{ __('general.next') }}
                    </button>
                @else
                    <div class="rounded-xl bg-red-50 p-4 text-center dark:bg-red-900/20">
                        <p class="text-3xl">❌</p>
                        <p class="mt-2 text-lg font-bold text-red-700 dark:text-red-400">{{ __('sessions.wrong') }}</p>
                        @if ($wrongBehaviour === 'retry_penalty' && $penaltyPoints)
                            <p class="text-sm text-red-600 dark:text-red-400">{{ __('sessions.wrong_penalty', ['points' => $penaltyPoints]) }}</p>
                        @endif
                    </div>

                    @if ($wrongBehaviour === 'lockout' && $lockoutRemaining > 0)
                        {{-- Locked out: input hidden, countdown ticks, auto-retry when it reaches 0 --}}
                        <div
                            x-data="{ remaining: @js($lockoutRemaining) }"
                            x-init="const t = setInterval(() => { if (--remaining <= 0) { clearInterval(t); $wire.tryAgain(); } }, 1000)"
                            class="w-full rounded-xl bg-gray-200 px-4 py-3.5 text-center font-heading text-sm font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                        >
                            {{ __('sessions.locked_out') }} <span x-text="remaining"></span>{{ __('sessions.seconds_short') }}
                        </div>
                    @else
                        <button
                            wire:click="tryAgain"
                            class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500"
                        >
                            {{ __('sessions.try_again') }}
                        </button>
                    @endif
                @endif
            @endif
        </div>
    @endif
</div>
