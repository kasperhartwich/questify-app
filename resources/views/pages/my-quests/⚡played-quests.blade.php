<?php

use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('My Quests')]
class extends Component
{
    use WithPagination;

    public string $tab = 'played';

    public function render(): mixed
    {
        $participations = SessionParticipant::query()
            ->where('user_id', Auth::id())
            ->with(['questSession.quest.category'])
            ->latest()
            ->paginate(15);

        return view('pages.my-quests.played-quests-view', [
            'participations' => $participations,
        ]);
    }
};
?>
