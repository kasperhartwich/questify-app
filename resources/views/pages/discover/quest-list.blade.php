<?php

use App\Enums\Difficulty;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Discover')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $difficulty = '';

    #[Url]
    public string $sortBy = 'latest';

    #[Url]
    public string $cursor = '';

    /** @var array<int, array<string, mixed>> */
    public array $questsData = [];

    /** @var array<int, array<string, mixed>> */
    public array $categoriesData = [];

    public ?string $nextCursor = null;

    public ?string $prevCursor = null;

    public function mount(): void
    {
        $categoryResponse = $this->tryApiCall(fn () => $this->api->categories()->list()) ?? ['data' => []];
        $this->categoriesData = $categoryResponse['data'] ?? [];

        $this->loadQuests();
    }

    public function updatedSearch(): void
    {
        $this->cursor = '';
        $this->loadQuests();
    }

    public function updatedCategory(): void
    {
        $this->cursor = '';
        $this->loadQuests();
    }

    public function updatedDifficulty(): void
    {
        $this->cursor = '';
        $this->loadQuests();
    }

    public function updatedCursor(): void
    {
        $this->loadQuests();
    }

    public function render(): mixed
    {
        return view('pages.discover.quest-list-view', [
            'quests' => $this->toObjectCollection($this->questsData),
            'categories' => $this->toObjectCollection($this->categoriesData),
            'difficulties' => Difficulty::cases(),
        ]);
    }

    private function loadQuests(): void
    {
        $filters = array_filter([
            'search' => $this->search ?: null,
            'category_id' => $this->category ?: null,
            'difficulty' => $this->difficulty ?: null,
            'cursor' => $this->cursor ?: null,
        ]);

        $response = $this->tryApiCall(fn () => $this->api->quests()->list($filters)) ?? ['data' => [], 'meta' => []];

        $this->questsData = $response['data'] ?? [];
        $this->nextCursor = $response['meta']['next_cursor'] ?? null;
        $this->prevCursor = $response['meta']['prev_cursor'] ?? null;
    }
};
?>
