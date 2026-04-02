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
        $this->addCheckpoint();
    }

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step = min(5, $this->step + 1);
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
        $this->saveQuest(publish: true);
    }

    public function saveAsDraft(): void
    {
        $this->saveQuest();
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
                'title' => $checkpoint['title'],
                'description' => $checkpoint['description'] ?: null,
                'latitude' => $checkpoint['latitude'],
                'longitude' => $checkpoint['longitude'],
                'questions' => $questionsData,
            ];
        }

        $data = [
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'visibility' => $this->visibility,
            'estimated_duration_minutes' => 60,
            'wrong_answer_behaviour' => $this->wrongAnswerBehaviour,
            'checkpoints' => $checkpointsData,
        ];

        $coverImagePath = $this->coverImage ? $this->coverImage->getRealPath() : null;

        $response = $this->tryApiCall(fn () => $this->api->quests()->store($data, $coverImagePath));

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
                'categoryId' => ['required', 'in:' . implode(',', array_keys($this->categories))],
                'difficulty' => ['required', 'in:' . implode(',', array_column(Difficulty::cases(), 'value'))],
            ]),
            2 => $this->validate([
                'checkpoints' => ['required', 'array', 'min:1'],
                'checkpoints.*.title' => ['required', 'string', 'max:255'],
            ]),
            3 => $this->validateQuestions(),
            4 => $this->validate([
                'playMode' => ['required', 'in:' . implode(',', array_column(PlayMode::cases(), 'value'))],
                'wrongAnswerBehaviour' => ['required', 'in:' . implode(',', array_column(WrongAnswerBehaviour::cases(), 'value'))],
            ]),
            5 => null,
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
