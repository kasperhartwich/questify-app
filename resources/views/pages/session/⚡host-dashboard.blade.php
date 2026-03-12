<?php

use App\Enums\SessionStatus;
use App\Events\SessionEnded;
use App\Models\QuestSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Host Dashboard')]
class extends Component
{
    public QuestSession $session;

    public array $participants = [];

    public int $totalCheckpoints = 0;

    public function mount(string $code): void
    {
        $this->session = QuestSession::where('join_code', $code)
            ->with('quest.checkpoints')
            ->firstOrFail();

        abort_if(Auth::id() !== $this->session->host_id, 403);

        $this->totalCheckpoints = $this->session->quest->checkpoints->count();
        $this->loadParticipants();
    }

    public function loadParticipants(): void
    {
        $this->participants = $this->session->participants()
            ->with(['user:id,name', 'checkpointProgress'])
            ->orderByDesc('score')
            ->get()
            ->map(function ($p) {
                $completedCheckpoints = $p->checkpointProgress
                    ->where('is_correct', true)
                    ->pluck('checkpoint_id')
                    ->unique()
                    ->count();

                return [
                    'id' => $p->id,
                    'display_name' => $p->display_name,
                    'user_name' => $p->user?->name,
                    'score' => $p->score,
                    'checkpoints_completed' => $completedCheckpoints,
                    'total_checkpoints' => $this->totalCheckpoints,
                    'status' => $p->finished_at ? 'finished' : 'playing',
                    'finished_at' => $p->finished_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }

    #[On('echo-presence:session.{session.join_code},CheckpointCompleted')]
    public function onCheckpointCompleted(): void
    {
        $this->loadParticipants();
    }

    #[On('echo-presence:session.{session.join_code},LeaderboardUpdated')]
    public function onLeaderboardUpdated(): void
    {
        $this->loadParticipants();
    }

    #[On('echo-presence:session.{session.join_code},QuestCompleted')]
    public function onQuestCompleted(): void
    {
        $this->loadParticipants();
    }

    #[On('echo-presence:session.{session.join_code},ParticipantJoined')]
    public function onParticipantJoined(): void
    {
        $this->loadParticipants();
    }

    public function endSession(): void
    {
        $this->session->update([
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->session->participants()
            ->whereNull('finished_at')
            ->update(['finished_at' => now()]);

        broadcast(new SessionEnded($this->session->fresh()));

        $this->redirect('/session/' . $this->session->join_code . '/complete');
    }

    public function getListeners(): array
    {
        return [
            "echo-presence:session.{$this->session->join_code},CheckpointCompleted" => 'onCheckpointCompleted',
            "echo-presence:session.{$this->session->join_code},LeaderboardUpdated" => 'onLeaderboardUpdated',
            "echo-presence:session.{$this->session->join_code},QuestCompleted" => 'onQuestCompleted',
            "echo-presence:session.{$this->session->join_code},ParticipantJoined" => 'onParticipantJoined',
        ];
    }
};
?>

<div class="flex flex-col">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-4 text-white">
        <h1 class="text-lg font-bold">{{ __('sessions.host_dashboard') }}</h1>
        <p class="text-sm opacity-80">{{ $session->quest?->title }} — {{ $session->join_code }}</p>
        <div class="mt-2 flex gap-4 text-xs">
            <span>{{ count($participants) }} {{ __('sessions.participants') }}</span>
            <span>{{ collect($participants)->where('status', 'finished')->count() }} {{ __('sessions.finished') }}</span>
        </div>
    </div>

    <div class="flex-1 space-y-3 p-4">
        {{-- Participants Table --}}
        @foreach ($participants as $p)
            <div
                class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700"
                wire:key="host-p-{{ $p['id'] }}"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full
                            {{ $p['status'] === 'finished' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' }} text-sm font-bold">
                            {{ strtoupper(substr($p['display_name'], 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $p['display_name'] }}</p>
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $p['checkpoints_completed'] }}/{{ $p['total_checkpoints'] }} {{ __('quests.checkpoints') }}</span>
                                <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium
                                    {{ $p['status'] === 'finished' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ $p['status'] === 'finished' ? __('sessions.completed') : __('sessions.in_progress') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-lg font-bold text-gray-900 dark:text-white">{{ number_format($p['score']) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.score') }}</p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full {{ $p['status'] === 'finished' ? 'bg-green-500' : 'bg-indigo-500' }} transition-all"
                        style="width: {{ $p['total_checkpoints'] > 0 ? ($p['checkpoints_completed'] / $p['total_checkpoints']) * 100 : 0 }}%"
                    ></div>
                </div>
            </div>
        @endforeach

        @if (empty($participants))
            <div class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                {{ __('sessions.no_participants') }}
            </div>
        @endif
    </div>

    {{-- End Session Button --}}
    <div class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <button
            wire:click="endSession"
            wire:confirm="{{ __('sessions.end_confirm') }}"
            class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700"
        >
            {{ __('sessions.end') }}
        </button>
    </div>
</div>
