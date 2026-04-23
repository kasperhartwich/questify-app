<?php

use App\Enums\Difficulty;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Facades\Geolocation;

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

    public float $latitude = 55.6761;

    public float $longitude = 12.5683;

    /** @var array<int, array<string, mixed>> */
    public array $questsData = [];

    /** @var array<int, array<string, mixed>> */
    public array $categoriesData = [];

    public function mount(): void
    {
        $categoryResponse = $this->tryApiCall(fn () => $this->api->categories()->list()) ?? ['data' => []];
        $this->categoriesData = $categoryResponse['data'] ?? [];

        $this->loadQuests();

        try {
            Geolocation::getCurrentPosition();
        } catch (\Throwable) {
            // Not on native device
        }
    }

    #[OnNative(LocationReceived::class)]
    public function onLocationReceived(
        bool $success = false,
        float $latitude = 0,
        float $longitude = 0,
    ): void {
        if (! $success) {
            return;
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->loadQuests();
    }

    public function updatedSearch(): void
    {
        $this->loadQuests();
    }

    public function updatedCategory(): void
    {
        $this->loadQuests();
    }

    public function updatedDifficulty(): void
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
            'category_id' => $this->category ?: null,
            'difficulty' => $this->difficulty ?: null,
            'radius' => 50,
        ]);

        $response = $this->tryApiCall(fn () => $this->api->quests()->nearby(
            $this->latitude,
            $this->longitude,
            $filters,
        )) ?? ['data' => []];

        $questsData = $response['data'] ?? [];

        // Client-side search filter (nearby endpoint doesn't support search)
        if ($this->search) {
            $search = mb_strtolower($this->search);
            $questsData = array_values(array_filter($questsData, fn ($q) => str_contains(mb_strtolower($q['title'] ?? ''), $search)));
        }

        $this->questsData = $questsData;
    }
};
?>
