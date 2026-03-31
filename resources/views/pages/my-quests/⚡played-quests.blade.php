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

    public string $tab = 'playing';

    public string $cursor = '';

    public function archiveQuest(int $questId): void
    {
        $this->tryApiCall(fn () => $this->api->quests()->destroy($questId));
    }

    public function render(): mixed
    {
        $participations = collect();
        $createdQuests = collect();
        $nextCursor = null;

        if ($this->tab === 'playing' || $this->tab === 'history') {
            $response = $this->tryApiCall(fn () => $this->api->user()->sessions()) ?? ['data' => []];
            $participations = $this->toObjectCollection($response['data'] ?? []);
        }

        if ($this->tab === 'created') {
            $response = $this->tryApiCall(fn () => $this->api->user()->quests($this->cursor ?: null)) ?? ['data' => [], 'meta' => []];
            $createdQuests = $this->toObjectCollection($response['data'] ?? []);
            $nextCursor = $response['meta']['next_cursor'] ?? null;
        }

        return view('pages.my-quests.played-quests-view', [
            'participations' => $participations,
            'createdQuests' => $createdQuests,
            'nextCursor' => $nextCursor,
        ]);
    }
};
?>
