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

    public string $tab = 'played';

    public function render(): mixed
    {
        $response = $this->tryApiCall(fn () => $this->api->user()->sessions()) ?? ['data' => []];

        return view('pages.my-quests.played-quests-view', [
            'participations' => $response['data'] ?? [],
        ]);
    }
};
?>
