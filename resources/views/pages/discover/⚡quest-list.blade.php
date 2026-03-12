<?php

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Quest;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('Discover')]
class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $difficulty = '';

    #[Url]
    public string $sortBy = 'latest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedDifficulty(): void
    {
        $this->resetPage();
    }

    public function rendering(): void
    {
        //
    }

    public function render(): mixed
    {
        $query = Quest::query()
            ->published()
            ->visible()
            ->withAvg('ratings', 'rating')
            ->withCount(['checkpoints', 'ratings']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->category !== '') {
            $query->where('category_id', $this->category);
        }

        if ($this->difficulty !== '') {
            $query->where('difficulty', $this->difficulty);
        }

        $query->orderByDesc('published_at');

        return view('pages.discover.quest-list-view', [
            'quests' => $query->paginate(12),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'difficulties' => Difficulty::cases(),
        ]);
    }
};
?>
