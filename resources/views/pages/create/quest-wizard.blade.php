<?php

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestionType;
use App\Enums\WrongAnswerBehaviour;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Create Quest')]
class extends Component
{
    use HandlesApiErrors, WithApiClient, WithFileUploads;

    #[Session(key: 'quest_wizard.step')]
    public int $step = 1;

    /** Id of the draft saved to the backend, if the author saved one. */
    #[Session(key: 'quest_wizard.draftQuestId')]
    public ?int $draftQuestId = null;

    // Step 1: Basics
    #[Validate('nullable|string|max:255')]
    #[Session(key: 'quest_wizard.title')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    #[Session(key: 'quest_wizard.description')]
    public string $description = '';

    #[Validate('required')]
    #[Session(key: 'quest_wizard.categoryId')]
    public $categoryId = '';

    #[Validate('required')]
    #[Session(key: 'quest_wizard.difficulty')]
    public string $difficulty = '';

    #[Validate('nullable|image|max:2048')]
    public $coverImage;

    // Step 2: Checkpoints
    /** @var array<int, array{title: string, description: string, latitude: ?float, longitude: ?float}> */
    #[Session(key: 'quest_wizard.checkpoints')]
    public array $checkpoints = [];

    // Step 3: Questions (keyed by checkpoint index)
    /** @var array<int, array<int, array{body: string, type: string, hint: string, points: int, answers: array}>> */
    #[Session(key: 'quest_wizard.questions')]
    public array $questions = [];

    // Step 4: Game Rules
    #[Session(key: 'quest_wizard.playMode')]
    public string $playMode = 'solo';

    #[Session(key: 'quest_wizard.wrongAnswerBehaviour')]
    public string $wrongAnswerBehaviour = 'retry_free';

    #[Session(key: 'quest_wizard.timeLimitPerQuestion')]
    public ?int $timeLimitPerQuestion = 30;

    #[Session(key: 'quest_wizard.shuffleQuestions')]
    public bool $shuffleQuestions = false;

    #[Session(key: 'quest_wizard.shuffleAnswers')]
    public bool $shuffleAnswers = false;

    #[Session(key: 'quest_wizard.maxParticipants')]
    public ?int $maxParticipants = null;

    // Step 4 extras
    #[Session(key: 'quest_wizard.visibility')]
    public string $visibility = 'public';

    #[Session(key: 'quest_wizard.scoringSpeedBonus')]
    public bool $scoringSpeedBonus = false;

    #[Session(key: 'quest_wizard.scoringWrongPenalty')]
    public bool $scoringWrongPenalty = false;

    #[Session(key: 'quest_wizard.scoringCompletionBonus')]
    public bool $scoringCompletionBonus = true;

    // Step 3 navigation
    #[Session(key: 'quest_wizard.activeCheckpointIndex')]
    public int $activeCheckpointIndex = 0;

    public int $activeQuestionIndex = 0;

    // Computed
    public array $categories = [];

    /**
     * Statuses whose quest may still be edited. A published quest is live for
     * players, so it is opened read-only from the detail screen instead.
     */
    private const EDITABLE_STATUSES = ['draft', 'pending_review'];

    public function mount(?int $quest = null): void
    {
        if ($quest !== null && $quest !== $this->draftQuestId) {
            // Opening a different quest replaces whatever draft was in progress.
            $this->clearDraft();
        }

        $response = $this->tryApiCall(fn () => $this->api->categories()->list()) ?? ['data' => []];
        $this->categories = collect($response['data'] ?? [])
            ->pluck('name', 'id')
            ->toArray();

        // Both default to [] — each map tap appends exactly one checkpoint with
        // coordinates. Do NOT reset them here: #[Session] restores an in-progress
        // draft before mount() runs, and clearing would throw that away.

        if ($quest !== null) {
            $this->loadQuestForEditing($quest);
        }
    }

    /**
     * Fill the wizard from an existing quest so the author can keep working on
     * it. Published quests are not editable here.
     */
    private function loadQuestForEditing(int $questId): void
    {
        $response = $this->tryApiCall(fn () => $this->api->quests()->show($questId));

        if (! $response) {
            return;
        }

        $quest = $response['data'];

        if (! in_array($quest['status'] ?? '', self::EDITABLE_STATUSES, true)) {
            $this->dispatch('api-error', message: __('quests.published_not_editable'));
            $this->redirect('/quests/' . $questId);

            return;
        }

        $this->draftQuestId = $questId;
        $this->title = $quest['title'] ?? '';
        $this->description = $quest['description'] ?? '';
        $this->categoryId = $quest['category']['id'] ?? '';
        $this->difficulty = $quest['difficulty'] ?? '';
        $this->visibility = $quest['visibility'] ?? 'public';
        $this->wrongAnswerBehaviour = $quest['wrong_answer_behaviour'] ?? 'retry_free';

        $this->checkpoints = [];
        $this->questions = [];

        foreach ($quest['checkpoints'] ?? [] as $index => $checkpoint) {
            $this->checkpoints[] = [
                'title' => $checkpoint['title'] ?? '',
                'description' => $checkpoint['description'] ?? '',
                'latitude' => isset($checkpoint['latitude']) ? (float) $checkpoint['latitude'] : null,
                'longitude' => isset($checkpoint['longitude']) ? (float) $checkpoint['longitude'] : null,
            ];

            $this->questions[$index] = collect($checkpoint['questions'] ?? [])
                ->map(fn (array $question): array => [
                    'body' => $question['question_text'] ?? '',
                    'type' => $question['question_type'] ?? QuestionType::MultipleChoice->value,
                    'hint' => $question['hint'] ?? '',
                    'points' => $question['points'] ?? 10,
                    'answers' => collect($question['answers'] ?? [])
                        ->map(fn (array $answer): array => [
                            'body' => $answer['answer_text'] ?? '',
                            'is_correct' => (bool) ($answer['is_correct'] ?? false),
                        ])->all(),
                ])->all();
        }

        $this->step = 1;
    }

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step = min(6, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function goToStep(int $step): void
    {
        if ($step <= $this->step) {
            $this->step = $step;
        }
    }

    public function addCheckpoint(): void
    {
        $index = count($this->checkpoints);
        $this->checkpoints[] = [
            'title' => '',
            'description' => '',
            'latitude' => null,
            'longitude' => null,
        ];
        $this->questions[$index] = [];
    }

    public function removeCheckpoint(int $index): void
    {
        if (count($this->checkpoints) <= 1) {
            return;
        }

        array_splice($this->checkpoints, $index, 1);
        array_splice($this->questions, $index, 1);
        $this->checkpoints = array_values($this->checkpoints);
        $this->questions = array_values($this->questions);
    }

    public function updateCheckpointCoordinates(int $index, float $lat, float $lng): void
    {
        if (isset($this->checkpoints[$index])) {
            $this->checkpoints[$index]['latitude'] = $lat;
            $this->checkpoints[$index]['longitude'] = $lng;
        }
    }

    public function addQuestion(int $checkpointIndex): void
    {
        $this->questions[$checkpointIndex][] = [
            'body' => '',
            'type' => QuestionType::MultipleChoice->value,
            'hint' => '',
            'points' => 10,
            'answers' => [
                ['body' => '', 'is_correct' => true],
                ['body' => '', 'is_correct' => false],
            ],
        ];
    }

    public function removeQuestion(int $checkpointIndex, int $questionIndex): void
    {
        array_splice($this->questions[$checkpointIndex], $questionIndex, 1);
        $this->questions[$checkpointIndex] = array_values($this->questions[$checkpointIndex]);
    }

    public function addAnswer(int $checkpointIndex, int $questionIndex): void
    {
        $this->questions[$checkpointIndex][$questionIndex]['answers'][] = [
            'body' => '',
            'is_correct' => false,
        ];
    }

    public function removeAnswer(int $checkpointIndex, int $questionIndex, int $answerIndex): void
    {
        $answers = &$this->questions[$checkpointIndex][$questionIndex]['answers'];
        if (count($answers) <= 2) {
            return;
        }

        array_splice($answers, $answerIndex, 1);
    }

    public function onQuestionTypeChanged(int $checkpointIndex, int $questionIndex): void
    {
        $type = $this->questions[$checkpointIndex][$questionIndex]['type'];

        if ($type === QuestionType::TrueFalse->value) {
            $this->questions[$checkpointIndex][$questionIndex]['answers'] = [
                ['body' => 'True', 'is_correct' => true],
                ['body' => 'False', 'is_correct' => false],
            ];
        } elseif ($type === QuestionType::OpenText->value) {
            $this->questions[$checkpointIndex][$questionIndex]['answers'] = [];
        }
    }

    public function publish(): void
    {
        $this->validateStep();
        $this->validateBeforeSave();
        $this->saveQuest(publish: true);
    }

    public function saveAsDraft(): void
    {
        $this->validateBeforeSave();
        $this->saveQuest();
    }

    private function validateBeforeSave(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $missingCoords = collect($this->checkpoints)
            ->filter(fn (array $cp): bool => empty($cp['latitude']) || empty($cp['longitude']))
            ->count();

        if ($missingCoords > 0) {
            $this->dispatch('api-error', message: __('quests.checkpoints_need_coordinates'));
            $this->step = 2;

            throw new \Illuminate\Validation\ValidationException(validator([], []));
        }
    }

    private function saveQuest(bool $publish = false): void
    {
        $checkpointsData = [];
        foreach ($this->checkpoints as $cpIndex => $checkpoint) {
            $questionsData = [];
            foreach ($this->questions[$cpIndex] ?? [] as $question) {
                $answersData = [];
                foreach ($question['answers'] ?? [] as $answer) {
                    $answersData[] = [
                        'answer_text' => $answer['body'],
                        'is_correct' => $answer['is_correct'],
                    ];
                }
                $questionsData[] = [
                    'question_text' => $question['body'],
                    'question_type' => $question['type'],
                    'answers' => $answersData,
                ];
            }
            $checkpointsData[] = [
                'title' => $checkpoint['title'] ?: __('general.checkpoint') . ' ' . ($cpIndex + 1),
                'description' => $checkpoint['description'] ?: null,
                'latitude' => $checkpoint['latitude'],
                'longitude' => $checkpoint['longitude'],
                'questions' => $questionsData,
            ];
        }

        $data = [
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'description' => $this->description ?: '',
            'difficulty' => $this->difficulty,
            'visibility' => $this->visibility,
            'estimated_duration_minutes' => 60,
            'wrong_answer_behaviour' => $this->wrongAnswerBehaviour,
            'checkpoints' => $checkpointsData,
        ];

        $coverImagePath = $this->coverImage ? $this->coverImage->getRealPath() : null;

        try {
            $response = $this->draftQuestId !== null
                ? $this->api->quests()->update($this->draftQuestId, $data, $coverImagePath)
                : $this->api->quests()->store($data, $coverImagePath);
        } catch (\App\Exceptions\Api\ApiValidationException $e) {
            $this->dispatch('api-error', message: collect($e->errors)->flatten()->first());

            return;
        } catch (\App\Exceptions\Api\ApiAuthenticationException) {
            session()->flush();
            $this->redirect(route('login'));

            return;
        } catch (\App\Exceptions\Api\ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());

            return;
        }

        if (! $response) {
            return;
        }

        $questId = $response['data']['id'];
        $this->draftQuestId = $questId;

        if ($publish) {
            $this->tryApiCall(fn () => $this->api->quests()->publish($questId));
        }

        $this->clearDraft();

        $this->redirect('/quests/' . $questId);
    }

    /**
     * Stop working on this quest: clear the wizard and start over. Anything
     * already saved to the backend stays in My Quests as a draft, so nothing
     * the author finished is thrown away.
     */
    public function discardQuest(): void
    {
        $this->clearDraft();
        $this->dispatch('api-error', message: __('quests.quest_discarded'));
    }

    /**
     * Drop the persisted draft and reset the wizard. Called after a successful
     * save and by the "start over" action.
     */
    public function clearDraft(): void
    {
        foreach (array_keys(session()->all()) as $key) {
            if (str_starts_with((string) $key, 'quest_wizard.')) {
                session()->forget($key);
            }
        }

        $this->step = 1;
        $this->draftQuestId = null;
        $this->title = '';
        $this->description = '';
        $this->categoryId = '';
        $this->difficulty = '';
        $this->checkpoints = [];
        $this->questions = [];
        $this->activeCheckpointIndex = 0;
        $this->activeQuestionIndex = 0;
    }

    private function validateStep(): void
    {
        match ($this->step) {
            // Step 1 asks for a name and description but does not insist: authors
            // often drop pins first and write the copy on the review screen,
            // where both become required.
            1 => null,
            // Difficulty is chosen on step 5 (Details) and validated there — requiring it here
            // would silently block advancing past step 1 (its error has no field to render).
            2 => $this->validate([
                'checkpoints' => ['required', 'array', 'min:2'],
                'checkpoints.*.title' => ['nullable', 'string', 'max:255'],
                'checkpoints.*.latitude' => ['required', 'numeric'],
                'checkpoints.*.longitude' => ['required', 'numeric'],
            ], [
                'checkpoints.min' => __('quests.checkpoints_min'),
                'checkpoints.*.latitude.required' => __('quests.checkpoints_need_coordinates'),
                'checkpoints.*.longitude.required' => __('quests.checkpoints_need_coordinates'),
            ]),
            3 => $this->validateQuestions(),
            4 => $this->validate([
                'playMode' => ['required', 'in:' . implode(',', array_column(PlayMode::cases(), 'value'))],
                'wrongAnswerBehaviour' => ['required', 'in:' . implode(',', array_column(WrongAnswerBehaviour::cases(), 'value'))],
            ]),
            5 => $this->validate([
                'categoryId' => ['required', 'in:' . implode(',', array_keys($this->categories))],
                'difficulty' => ['required', 'in:' . implode(',', array_column(Difficulty::cases(), 'value'))],
            ]),
            6 => null,
            default => null,
        };
    }

    private function validateQuestions(): void
    {
        // Spec 5.9 step 3: every checkpoint must have at least one question.
        foreach ($this->checkpoints as $cpIndex => $checkpoint) {
            $cpQuestions = $this->questions[$cpIndex] ?? [];

            if (empty($cpQuestions)) {
                $this->dispatch('api-error', message: __('quests.checkpoint_needs_question'));

                throw new \Illuminate\Validation\ValidationException(validator([], []));
            }

            $this->validate([
                "questions.{$cpIndex}.*.body" => ['required', 'string'],
                "questions.{$cpIndex}.*.points" => ['required', 'integer', 'min:1'],
            ]);

            // Choice answers need text and exactly one correct option. Without
            // this the backend rejected the save with a raw, untranslated field
            // path (checkpoints.0.questions.0.answers.0.answer_text).
            foreach ($cpQuestions as $qIndex => $question) {
                if (($question['type'] ?? '') === QuestionType::OpenText->value) {
                    continue;
                }

                $answers = $question['answers'] ?? [];

                $blank = collect($answers)->filter(fn (array $a): bool => trim((string) ($a['body'] ?? '')) === '')->isNotEmpty();
                $correct = collect($answers)->filter(fn (array $a): bool => (bool) ($a['is_correct'] ?? false))->count();

                if ($blank || count($answers) < 2) {
                    $this->dispatch('api-error', message: __('quests.answers_need_text'));

                    throw new \Illuminate\Validation\ValidationException(validator([], []));
                }

                if ($correct !== 1) {
                    $this->dispatch('api-error', message: __('quests.answers_need_one_correct'));

                    throw new \Illuminate\Validation\ValidationException(validator([], []));
                }
            }
        }
    }

    public function render(): mixed
    {
        return view('pages.create.quest-wizard-view');
    }
};
?>
