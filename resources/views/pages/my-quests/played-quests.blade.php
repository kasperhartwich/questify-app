<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('My Quests')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $tab = 'favourites';

    public string $cursor = '';

    /** @var array<int, array<string, mixed>> */
    public array $participationsData = [];

    /** @var array<int, array<string, mixed>> */
    public array $createdQuestsData = [];

    /** @var array<int, array<string, mixed>> */
    public array $favouriteQuestsData = [];

    public ?string $nextCursor = null;

    public function mount(): void
    {
        $this->loadTabData();
    }

    public function updatedTab(): void
    {
        $this->cursor = '';
        $this->loadTabData();
    }

    public function updatedCursor(): void
    {
        $this->loadTabData();
    }

    public function archiveQuest(int $questId): void
    {
        $this->tryApiCall(fn () => $this->api->quests()->destroy($questId));
        $this->loadTabData();
    }

    public function render(): mixed
    {
        return view('pages.my-quests.played-quests-view', [
            'participations' => $this->toObjectCollection($this->participationsData),
            'createdQuests' => $this->toObjectCollection($this->createdQuestsData),
            'favouriteQuests' => $this->toObjectCollection($this->favouriteQuestsData),
        ]);
    }

    private function loadTabData(): void
    {
        $this->participationsData = [];
        $this->createdQuestsData = [];
        $this->favouriteQuestsData = [];
        $this->nextCursor = null;

        if ($this->tab === 'playing' || $this->tab === 'history') {
            $response = $this->tryApiCall(fn () => $this->api->user()->sessions()) ?? ['data' => []];
            $this->participationsData = $response['data'] ?? [];
        }

        if ($this->tab === 'created') {
            $response = $this->tryApiCall(fn () => $this->api->user()->quests($this->cursor ?: null)) ?? ['data' => [], 'meta' => []];
            $this->createdQuestsData = $response['data'] ?? [];
            $this->nextCursor = $response['meta']['next_cursor'] ?? null;
        }

        if ($this->tab === 'favourites') {
            try {
                $response = $this->api->user()->favourites($this->cursor ?: null);
                $this->favouriteQuestsData = $response['data'] ?? [];
                $this->nextCursor = $response['meta']['next_cursor'] ?? null;
            } catch (\App\Exceptions\Api\ApiException) {
                // Gracefully handle if the favourites endpoint is unavailable
            }
        }
    }
};
?>
