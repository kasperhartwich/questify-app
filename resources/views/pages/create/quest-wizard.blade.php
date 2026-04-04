<?php

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestionType;
use App\Enums\WrongAnswerBehaviour;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Create Quest')]
class extends Component
{
    use HandlesApiErrors, WithApiClient, WithFileUploads;

    public int $step = 1;

    // Step 1: Basics
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required')]
    public $categoryId = '';

    #[Validate('required')]
    public string $difficulty = '';

    #[Validate('nullable|image|max:2048')]
    public $coverImage;

    // Step 2: Checkpoints
    /** @var array<int, array{title: string, description: string, latitude: ?float, longitude: ?float}> */
    public array $checkpoints = [];

    // Step 3: Questions (keyed by checkpoint index)
    /** @var array<int, array<int, array{body: string, type: string, hint: string, points: int, answers: array}>> */
    public array $questions = [];

    // Step 4: Game Rules
    public string $playMode = 'solo';

    public string $wrongAnswerBehaviour = 'retry_free';

    public ?int $timeLimitPerQuestion = 30;

    public bool $shuffleQuestions = false;

    public bool $shuffleAnswers = false;

    public ?int $maxParticipants = null;

    // Step 4 extras
    public string $visibility = 'public';

    public bool $scoringSpeedBonus = false;

    public bool $scoringWrongPenalty = false;

    public bool $scoringCompletionBonus = true;

    // Step 3 navigation
    public int $activeCheckpointIndex = 0;

    public int $activeQuestionIndex = 0;

    // Computed
    public array $categories = [];

    public function mount(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->categories()->list()) ?? ['data' => []];
        $this->categories = collect($response['data'] ?? [])
            ->pluck('name', 'id')
            ->toArray();
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
            $response = $this->api->quests()->store($data, $coverImagePath);
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

        if ($publish) {
            $this->tryApiCall(fn () => $this->api->quests()->publish($questId));
        }

        $this->redirect('/quests/' . $questId);
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'title' => ['required', 'string', 'max:255'],
            ]),
            2 => $this->validate([
                'checkpoints' => ['required', 'array', 'min:1'],
                'checkpoints.*.title' => ['nullable', 'string', 'max:255'],
                'checkpoints.*.latitude' => ['required', 'numeric'],
                'checkpoints.*.longitude' => ['required', 'numeric'],
            ], [
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
        foreach ($this->questions as $cpIndex => $cpQuestions) {
            if (empty($cpQuestions)) {
                continue;
            }

            $this->validate([
                "questions.{$cpIndex}.*.body" => ['required', 'string'],
                "questions.{$cpIndex}.*.points" => ['required', 'integer', 'min:1'],
            ]);
        }
    }

    public function render(): mixed
    {
        return view('pages.create.quest-wizard-view');
    }
};
?>
