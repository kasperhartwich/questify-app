<?php

use App\Enums\QuestionType;
use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
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

    public int $lastSpeedBonus = 0;

    public bool $showFeedback = false;

    /** Wrong-answer behaviour returned by the API for the last incorrect attempt. */
    public ?string $wrongBehaviour = null;

    /** Points deducted on the last incorrect attempt (retry_penalty). */
    public ?int $penaltyPoints = null;

    /** Hint revealed for the current question (three_strikes_hint). Persists until the question is passed. */
    public ?string $revealedHint = null;

    /** Seconds remaining on a lockout after an incorrect attempt (lockout). */
    public int $lockoutRemaining = 0;

    /** Length of the running lockout, for the ring. */
    public int $lockoutTotal = 0;

    public array $answeredQuestionIds = [];

    public bool $questComplete = false;

    public bool $checkpointComplete = false;

    #[On('echo:session.{code},SessionEnded')]
    public function onSessionEnded(): void
    {
        $this->redirect('/session/' . $this->code . '/complete');
    }

    protected function endsWithSession(): bool
    {
        return true;
    }

    public function mount(string $code, int $checkpoint): void
    {
        $this->code = $code;
        $this->participantId = session('questify_participant_id', 0);

        // A signed-in player whose participant id was lost is found again in the session's
        // participant list. Without one the questions cannot be fetched at all.
        if (! $this->participantId && Auth::check()) {
            $session = $this->tryApiCall(fn () => $this->api->sessions()->show($code));

            foreach ($session['data']['participants'] ?? [] as $participant) {
                if (($participant['user_id'] ?? null) === Auth::id()) {
                    $this->participantId = $participant['id'];
                    session()->put('questify_participant_id', $participant['id']);
                    break;
                }
            }
        }

        if (! $this->participantId) {
            $this->redirect('/session/' . $code . '/play');

            return;
        }

        // Fetch checkpoint questions via the arrived endpoint, proving proximity with the
        // player's latest GPS fix recorded by the navigation screen. The server rejects the
        // call (422) if the player is outside the checkpoint radius, so questions can't be
        // reached without actually arriving — bounce back to navigation in that case.
        $response = $this->tryApiCall(fn () => $this->api->gameplay()->arrived(
            $this->code,
            $this->participantId,
            $checkpoint,
            (float) session('questify_player_lat', 0),
            (float) session('questify_player_lng', 0),
        ));

        if (! $response) {
            // An ended session has already sent the player home with its message.
            if (! session()->has('session_ended')) {
                $this->redirect('/session/' . $this->code . '/play');
            }

            return;
        }

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

        // The server keeps the lockout, so it is still running after the player left and came back.
        $this->startLockout((int) data_get($response, 'meta.retry_after', 0));
    }

    private function startLockout(int $seconds): void
    {
        $this->lockoutRemaining = max(0, $seconds);
        $this->lockoutTotal = $this->lockoutRemaining;
    }

    public function getCurrentQuestionProperty(): ?object
    {
        foreach ($this->questions as $index => $question) {
            if (! in_array($question['id'], $this->answeredQuestionIds)) {
                $this->currentQuestionIndex = $index;

                $obj = (object) $question;
                $obj->type = QuestionType::tryFrom($question['question_type'] ?? '') ?? QuestionType::MultipleChoice;
                $obj->body = $question['question_text'] ?? $question['body'] ?? '';
                $obj->points = $question['points'] ?? null;
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

        if ($this->lockoutRemaining > 0) {
            return;
        }

        try {
            $response = $this->api->gameplay()->answer(
                $this->code,
                $this->participantId,
                $currentQuestion->id,
                $answerId,
                $answerText,
            );
        } catch (ApiException $e) {
            // Still locked on the server (another phone, or a clock that ran ahead): show its countdown.
            if ($e->statusCode === 423 && ($e->body['code'] ?? null) === 'answer_locked') {
                $this->startLockout((int) ($e->body['retry_after'] ?? 0));

                return;
            }

            $response = $this->tryApiCall(fn () => throw $e);
        }

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
        $this->lastSpeedBonus = (int) ($data['speed_bonus'] ?? 0);
        $this->answeredQuestionIds[] = $currentQuestion->id;
        $this->wrongBehaviour = null;
        $this->penaltyPoints = null;
        $this->revealedHint = null;
        $this->startLockout(0);

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
        $this->startLockout(0);

        match ($this->wrongBehaviour) {
            'retry_penalty' => $this->penaltyPoints = (int) ($data['penalty'] ?? 0),
            'lockout' => $this->startLockout(isset($data['retry_after']) ? (int) $data['retry_after'] : $this->secondsUntil($data['locked_until'] ?? null)),
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
        $this->startLockout(0);
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
        $this->startLockout(0);

        if ($this->currentQuestion === null || $this->checkpointComplete) {
            if ($this->questComplete) {
                $this->redirect('/session/' . $this->code . '/complete');
            } else {
                $key = 'questify_checkpoint_index.' . $this->code;
                session()->put($key, (int) session($key, 0) + 1);
                $this->redirect('/session/' . $this->code . '/play');
            }
        }
    }
};
?>

@php
    $question = $this->currentQuestion;
    $isTrueFalse = $question?->type === \App\Enums\QuestionType::TrueFalse;
    $isOpenText = $question?->type === \App\Enums\QuestionType::OpenText;
    $progress = $totalQuestions > 0 ? (($currentQuestionIndex + ($showFeedback && $lastAnswerCorrect ? 1 : 0)) / $totalQuestions) * 100 : 0;
    $progress = max($progress, 5);
    $answers = $showFeedback && $lastAnswerCorrect
        ? $question->answers->sortBy(fn ($a) => $a->id === $selectedAnswerId ? 0 : 1)
        : $question->answers;
    $locked = $lockoutRemaining > 0;
    $typeLabel = $isTrueFalse ? __('sessions.type_true_false') : ($isOpenText ? __('sessions.type_open_text') : __('sessions.type_multiple_choice'));
@endphp

<div class="flex flex-col">
    {{-- Progress --}}
    <div class="px-4 pt-3">
        <p class="mb-1 text-right text-xs text-muted">{{ $currentQuestionIndex + 1 }}/{{ $totalQuestions }}</p>
        <div class="h-1 overflow-hidden rounded-full bg-cream-border">
            <div class="h-full rounded-full bg-forest-600 transition-all" style="width: {{ $progress }}%"></div>
        </div>
    </div>

    @if ($question)
        <div class="flex-1 space-y-3 p-4">
            {{-- Question card --}}
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-cream-border">
                <span class="mb-2 inline-block rounded-full bg-success px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-forest-600" data-testid="question-type-pill">{{ $typeLabel }}</span>
                <h2 class="font-heading text-base font-bold text-bark">{{ $question->body }}</h2>
                @if ($question->points)
                    <p class="mt-2 flex items-center gap-1.5 text-xs text-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>{{ $question->points }} {{ __('quests.points') }}
                    </p>
                @endif
            </div>

            {{-- Result block --}}
            @if ($showFeedback)
                @if ($lastAnswerCorrect)
                    <div class="rounded-xl bg-success px-4 py-6 text-center ring-1 ring-forest-100" data-testid="answer-result-correct">
                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-forest-600 text-white">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-testid="answer-correct-icon"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <p class="mt-3 font-heading text-lg font-bold text-forest-600">{{ __('sessions.correct') }}</p>
                        <p class="text-xs font-medium text-forest-400">+{{ (int) $lastPointsEarned }} {{ __('quests.points') }}</p>
                        @if ($lastSpeedBonus > 0)
                            <p class="text-xs text-forest-400">{{ __('sessions.speed_bonus_included', ['points' => $lastSpeedBonus]) }}</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl bg-coral-light px-4 py-6 text-center ring-1 ring-coral/30" data-testid="answer-result-wrong">
                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-coral text-white">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-testid="answer-wrong-icon"><path d="M7 7l10 10M17 7L7 17"/></svg>
                        </span>
                        <p class="mt-3 font-heading text-lg font-bold text-coral">{{ __('sessions.not_quite') }}</p>
                        @if ($wrongBehaviour === 'retry_penalty' && $penaltyPoints)
                            <p class="text-xs font-medium text-coral">{{ __('sessions.wrong_penalty', ['points' => $penaltyPoints]) }}</p>
                        @else
                            <p class="text-xs font-medium text-coral">+0 {{ __('quests.points') }}</p>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Revealed hint (three_strikes_hint): stays visible while retrying --}}
            @if ($revealedHint)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ __('sessions.hint_label') }}</p>
                    <p class="mt-1 text-sm text-amber-800">{{ $revealedHint }}</p>
                </div>
            @endif

            {{-- Answers --}}
            @if ($isOpenText)
                @if (! $showFeedback)
                    <textarea
                        wire:model="openEndedAnswer"
                        @disabled($locked)
                        rows="4"
                        class="w-full rounded-xl border border-cream-border bg-white px-3 py-2 text-sm text-bark"
                        placeholder="{{ __('sessions.type_answer') }}"
                    ></textarea>
                @endif
            @else
                <div class="space-y-2">
                    @foreach ($answers as $index => $answer)
                        @php
                            $picked = $selectedAnswerId === $answer->id;
                            $answerText = mb_strtolower(trim($answer->body));
                            $isTrueRow = in_array($answerText, ['true', 'sandt'], true)
                                || (! in_array($answerText, ['false', 'falsk'], true) && $index === 0);
                            $isWrongPick = $showFeedback && ! $lastAnswerCorrect && $picked;
                            $faded = ($selectedAnswerId !== null || $showFeedback) && ! $picked;
                            $rowClass = $isWrongPick
                                ? 'border-coral bg-coral-light text-coral'
                                : ($picked ? 'border-bark bg-success text-bark' : 'border-cream-border bg-white text-bark');
                            $badgeClass = $isWrongPick
                                ? 'bg-coral text-white'
                                : ($picked ? 'bg-forest-600 text-white' : 'bg-cream-dark text-muted');
                        @endphp
                        <button
                            type="button"
                            @if (! $showFeedback && ! $locked) wire:click="$set('selectedAnswerId', {{ $answer->id }})" @else disabled @endif
                            class="flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-left text-sm font-semibold transition-colors {{ $rowClass }} {{ $faded ? 'opacity-50' : '' }}"
                            wire:key="answer-{{ $answer->id }}"
                            data-testid="answer-row"
                        >
                            <span data-testid="answer-badge" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-bold {{ $badgeClass }}">
                                @if ($isTrueFalse)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        @if ($isTrueRow)<path d="M5 12.5l4.5 4.5L19 7.5"/>@else<path d="M7 7l10 10M17 7L7 17"/>@endif
                                    </svg>
                                @else
                                    {{ chr(65 + $index) }}
                                @endif
                            </span>
                            <span class="flex-1">{{ $answer->body }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Actions --}}
            @if ($locked && ! $lastAnswerCorrect)
                {{-- Locked out: a ring counts the seconds down, the answers stay disabled, then the player may try again --}}
                <div
                    x-data="{ ends: Date.now() + {{ $lockoutRemaining }} * 1000, total: {{ max($lockoutTotal, 1) }}, remaining: {{ $lockoutRemaining }} }"
                    x-init="const t = setInterval(() => { remaining = Math.max(0, Math.ceil((ends - Date.now()) / 1000)); if (remaining <= 0) { clearInterval(t); $wire.tryAgain(); } }, 250)"
                    class="flex flex-col items-center gap-2 py-2"
                    role="timer"
                    aria-live="off"
                    data-testid="lockout-ring"
                    wire:key="lockout-{{ $lockoutTotal }}-{{ $lockoutRemaining }}"
                >
                    <div class="relative flex h-[84px] w-[84px] items-center justify-center">
                        <svg class="absolute inset-0 -rotate-90 text-amber-400" width="84" height="84" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                            <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-opacity="0.3" stroke-width="2"/>
                            <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-width="2" stroke-linecap="round" pathLength="100" :stroke-dasharray="Math.round((remaining / total) * 100) + ' 100'" style="transition: stroke-dasharray 0.3s linear"/>
                        </svg>
                        <span class="font-heading text-[28px] font-[800] text-bark" x-text="remaining" data-testid="lockout-seconds">{{ $lockoutRemaining }}</span>
                    </div>
                    <p class="text-xs font-medium text-muted">{{ __('sessions.wait_to_answer') }}</p>
                </div>
            @elseif (! $showFeedback)
                <button
                    wire:click="submitAnswer"
                    wire:loading.attr="disabled"
                    wire:target="submitAnswer"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500 disabled:bg-cream-border disabled:text-muted"
                    {{ (! $isOpenText && ! $selectedAnswerId) ? 'disabled' : '' }}
                >
                    {{ __('sessions.submit_answer') }}
                </button>
            @elseif ($lastAnswerCorrect)
                <button
                    wire:click="nextQuestion"
                    class="w-full rounded-xl bg-forest-600 px-4 py-3.5 font-heading text-sm font-bold text-white hover:bg-forest-700"
                >
                    {{ __('general.next') }}
                </button>
            @else
                <button
                    wire:click="tryAgain"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500"
                >
                    {{ __('sessions.try_again') }}
                </button>
            @endif
        </div>
    @endif
</div>
