<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('My Created Quests')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $cursor = '';

    public function archiveQuest(int $questId): void
    {
        $this->tryApiCall(fn () => $this->api->quests()->destroy($questId));
    }

    public function render(): mixed
    {
        $response = $this->tryApiCall(fn () => $this->api->user()->quests($this->cursor ?: null)) ?? ['data' => [], 'meta' => []];

        return view('pages.my-quests.created-quests-view', [
            'quests' => $response['data'] ?? [],
            'nextCursor' => $response['meta']['next_cursor'] ?? null,
        ]);
    }
};
?>
