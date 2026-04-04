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

    public function updatedSearch(): void
    {
        $this->cursor = '';
    }

    public function updatedCategory(): void
    {
        $this->cursor = '';
    }

    public function updatedDifficulty(): void
    {
        $this->cursor = '';
    }

    public function render(): mixed
    {
        $filters = array_filter([
            'search' => $this->search ?: null,
            'category_id' => $this->category ?: null,
            'difficulty' => $this->difficulty ?: null,
            'cursor' => $this->cursor ?: null,
        ]);

        $questResponse = $this->tryApiCall(fn () => $this->api->quests()->list($filters)) ?? ['data' => [], 'meta' => []];
        $categoryResponse = $this->tryApiCall(fn () => $this->api->categories()->list()) ?? ['data' => []];

        return view('pages.discover.quest-list-view', [
            'quests' => $this->toObjectCollection($questResponse['data'] ?? []),
            'nextCursor' => $questResponse['meta']['next_cursor'] ?? null,
            'prevCursor' => $questResponse['meta']['prev_cursor'] ?? null,
            'categories' => $this->toObjectCollection($categoryResponse['data'] ?? []),
            'difficulties' => Difficulty::cases(),
        ]);
    }
};
?>
