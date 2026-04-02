<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Host Dashboard')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $code = '';

    public array $sessionData = [];

    public array $participants = [];

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->sessions()->dashboard($this->code));
        $data = $response['data'] ?? [];
        $this->sessionData = $data['session'] ?? [];
        $this->participants = collect($data['participants'] ?? [])
            ->map(fn ($p) => [
                'id' => $p['id'],
                'display_name' => $p['display_name'],
                'score' => $p['total_score'] ?? 0,
                'current_checkpoint_index' => $p['current_checkpoint_index'] ?? 0,
                'status' => $p['quest_completed_at'] ? 'finished' : 'playing',
            ])
            ->toArray();
    }

    #[On('echo-presence:session.{code},CheckpointCompleted')]
    public function onCheckpointCompleted(): void
    {
        $this->loadDashboard();
    }

    #[On('echo-presence:session.{code},LeaderboardUpdated')]
    public function onLeaderboardUpdated(): void
    {
        $this->loadDashboard();
    }

    #[On('echo-presence:session.{code},QuestCompleted')]
    public function onQuestCompleted(): void
    {
        $this->loadDashboard();
    }

    #[On('echo-presence:session.{code},ParticipantJoined')]
    public function onParticipantJoined(): void
    {
        $this->loadDashboard();
    }

    #[On('confirm-end-session')]
    public function endSession(): void
    {
        $this->tryApiCall(fn () => $this->api->sessions()->end($this->code));

        $this->redirect('/session/' . $this->code . '/complete');
    }
};
?>

<div class="flex flex-col">
    {{-- Header --}}
    <div class="relative overflow-hidden bg-forest-600 px-4 py-4 text-white">
        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-amber-400/10"></div>
        <h1 class="font-heading text-lg font-bold">{{ __('sessions.host_dashboard') }}</h1>
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
                            {{ $p['status'] === 'finished' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-forest-100 text-forest-700 dark:bg-forest-900/30 dark:text-forest-400' }} text-sm font-bold">
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
                        class="h-full rounded-full {{ $p['status'] === 'finished' ? 'bg-green-500' : 'bg-forest-500' }} transition-all"
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
            wire:click="$dispatch('show-dialog', {
                type: 'destructive',
                title: '{{ __('sessions.end_confirm_title') }}',
                message: '{{ __('sessions.end_confirm_message') }}',
                confirmLabel: '{{ __('sessions.end') }}',
                cancelLabel: '{{ __('general.cancel') }}',
                confirmEvent: 'confirm-end-session'
            })"
            class="w-full rounded-lg bg-coral px-4 py-3 font-semibold text-white hover:bg-coral/90"
        >
            {{ __('sessions.end') }}
        </button>
    </div>
</div>
