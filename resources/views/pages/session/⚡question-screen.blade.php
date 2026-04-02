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

    public array $checkpoint = [];

    public array $questions = [];

    public int $currentQuestionIndex = 0;

    public int $totalQuestions = 0;

    public ?int $selectedAnswerId = null;

    public string $openEndedAnswer = '';

    public ?bool $lastAnswerCorrect = null;

    public ?int $lastPointsEarned = null;

    public bool $showFeedback = false;

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
            $this->checkpoint = $response['data'] ?? [];
            $this->questions = $this->checkpoint['questions'] ?? [];
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
                $obj->answers = collect($question['answers'] ?? [])
                    ->map(fn ($a) => (object) [
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

        $answerId = null;
        $answerText = null;

        if (($currentQuestion['question_type'] ?? '') === QuestionType::OpenText->value) {
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
            $currentQuestion['id'],
            $answerId,
            $answerText,
        ));

        if (! $response) {
            return;
        }

        $data = $response['data'] ?? [];

        $this->lastAnswerCorrect = $data['correct'] ?? false;
        $this->lastPointsEarned = $data['score_earned'] ?? 0;
        $this->showFeedback = true;

        if ($this->lastAnswerCorrect) {
            $this->answeredQuestionIds[] = $currentQuestion['id'];
        }

        $next = $data['next'] ?? 'question';
        if ($next === 'quest_complete') {
            $this->questComplete = true;
            $this->checkpointComplete = true;
        } elseif ($next === 'checkpoint_complete') {
            $this->checkpointComplete = true;
        }
    }

    public function nextQuestion(): void
    {
        $this->selectedAnswerId = null;
        $this->openEndedAnswer = '';
        $this->showFeedback = false;

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
            <span>{{ $checkpoint['title'] ?? '' }}</span>
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
                    class="w-full rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500 disabled:opacity-50"
                    {{ ($this->currentQuestion->type !== \App\Enums\QuestionType::OpenText && !$selectedAnswerId) ? 'disabled' : '' }}
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
                    class="w-full rounded-xl bg-forest-600 px-4 py-3.5 font-heading text-sm font-bold text-white hover:bg-forest-700"
                >
                    {{ __('general.next') }}
                </button>
            @endif
        </div>
    @endif
</div>
