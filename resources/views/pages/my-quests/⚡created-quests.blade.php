<?php

use App\Models\Quest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('My Created Quests')]
class extends Component
{
    use WithPagination;

    public function archiveQuest(int $questId): void
    {
        $quest = Quest::where('creator_id', Auth::id())->findOrFail($questId);
        $quest->update(['status' => \App\Enums\QuestStatus::Archived]);
    }

    public function render(): mixed
    {
        $quests = Quest::query()
            ->where('creator_id', Auth::id())
            ->withCount(['sessions', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->latest()
            ->paginate(15);

        return view('pages.my-quests.created-quests-view', [
            'quests' => $quests,
        ]);
    }
};
?>
