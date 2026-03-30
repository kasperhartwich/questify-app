<?php

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestionType;
use App\Enums\QuestStatus;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Category;
use App\Models\Quest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Title('Create Quest')]
class extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // Step 1: Basics
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|exists:categories,id')]
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

    // Computed
    public array $categories = [];

    public function mount(): void
    {
        $this->categories = Category::query()->orderBy('name')->pluck('name', 'id')->toArray();
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
        $this->saveQuest(QuestStatus::Published);
    }

    public function saveAsDraft(): void
    {
        $this->saveQuest(QuestStatus::Draft);
    }

    private function saveQuest(QuestStatus $status): void
    {
        $coverImagePath = null;
        if ($this->coverImage) {
            $coverImagePath = $this->coverImage->store('quest-covers', 'public');
        }

        $quest = Quest::create([
            'creator_id' => Auth::id(),
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_path' => $coverImagePath,
            'difficulty' => $this->difficulty,
            'status' => $status,
            'play_mode' => $this->playMode,
            'wrong_answer_behaviour' => $this->wrongAnswerBehaviour,
            'time_limit_per_question' => $this->timeLimitPerQuestion,
            'shuffle_questions' => $this->shuffleQuestions,
            'shuffle_answers' => $this->shuffleAnswers,
            'max_participants' => $this->maxParticipants,
            'published_at' => $status === QuestStatus::Published ? now() : null,
        ]);

        foreach ($this->checkpoints as $cpIndex => $checkpoint) {
            $cp = $quest->checkpoints()->create([
                'title' => $checkpoint['title'],
                'description' => $checkpoint['description'],
                'latitude' => $checkpoint['latitude'],
                'longitude' => $checkpoint['longitude'],
                'sort_order' => $cpIndex,
            ]);

            foreach ($this->questions[$cpIndex] ?? [] as $qIndex => $question) {
                $q = $cp->questions()->create([
                    'type' => $question['type'],
                    'body' => $question['body'],
                    'hint' => $question['hint'] ?: null,
                    'points' => $question['points'],
                    'sort_order' => $qIndex,
                ]);

                foreach ($question['answers'] ?? [] as $aIndex => $answer) {
                    $q->answers()->create([
                        'body' => $answer['body'],
                        'is_correct' => $answer['is_correct'],
                        'sort_order' => $aIndex,
                    ]);
                }
            }
        }

        $this->redirect('/quests/' . $quest->id);
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'categoryId' => ['required', 'exists:categories,id'],
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
