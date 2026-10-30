<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\WithApiClient;
use App\Services\TokenStorage;
use Livewire\Component;

new class extends Component
{
    use WithApiClient;

    public string $mode = 'join';

    public string $href = '/join';

    public ?float $progress = null;

    public function mount(bool $onQuestScreen = false): void
    {
        $this->useActiveParticipationIfSignedIn();

        if ($this->mode === 'join' && $onQuestScreen) {
            $this->mode = 'start';
        }
    }

    /**
     * Asks the quest screen to start its quest, with the play mode chosen there.
     */
    public function startQuest(): void
    {
        $this->dispatch('centre-start-quest');
    }

    private function useActiveParticipationIfSignedIn(): void
    {
        if (! TokenStorage::has()) {
            return;
        }

        try {
            $response = $this->api->user()->sessions();
        } catch (ApiException) {
            return;
        }

        $this->useActiveParticipation($response['data'] ?? []);
    }

    /**
     * Switches to Continue mode for the first participation whose session is
     * still waiting or being played. The list is either flat or paginated.
     *
     * @param  array<int|string, mixed>  $participations
     */
    private function useActiveParticipation(array $participations): void
    {
        $participations = $participations['data'] ?? $participations;

        foreach ($participations as $participation) {
            $session = $participation['session'] ?? $participation['quest_session'] ?? [];
            $status = $session['status'] ?? null;
            $code = $session['session_code'] ?? null;

            if ($code === null || ! in_array($status, ['waiting', 'active'], true) || ! empty($participation['quest_completed_at'])) {
                continue;
            }

            $quest = $session['quest'] ?? [];
            $total = (int) ($quest['checkpoints_count'] ?? $quest['checkpoint_count'] ?? 0);

            $this->mode = 'continue';
            $this->href = $status === 'active' ? route('session.play', $code, false) : route('session.lobby', $code, false);
            $this->progress = $total > 0
                ? min(1, max(0, (int) ($participation['current_checkpoint_index'] ?? 0) / $total))
                : null;

            return;
        }
    }
};
?>

@php
    $label = match ($mode) {
        'start' => __('general.centre_start'),
        'continue' => __('general.centre_continue'),
        default => __('general.centre_join'),
    };
    $disc = match ($mode) {
        'start' => 'bg-amber-400 shadow-[0_6px_18px_rgba(245,166,35,0.55)]',
        'continue' => 'border border-cream-border bg-cream shadow-[0_6px_18px_rgba(11,61,46,0.28)]',
        default => 'bg-forest-600 shadow-[0_6px_18px_rgba(11,61,46,0.45)]',
    };
    $ring = $mode === 'start' ? 'text-forest-600' : 'text-amber-400';
    $logo = $mode === 'start' ? 'bg-forest-600 text-amber-400' : 'bg-amber-400 text-forest-600';
@endphp

@if ($mode === 'start')
    <button
        type="button"
        wire:click="startQuest"
        data-centre-mode="start"
        aria-label="{{ $label }}"
        class="relative z-10 flex flex-[1.3] items-center justify-center"
    >
@else
    <a
        href="{{ $href }}"
        wire:navigate.hover
        data-centre-mode="{{ $mode }}"
        aria-label="{{ $label }}"
        class="relative z-10 flex flex-[1.3] items-center justify-center"
    >
@endif
        <div class="-mt-9 flex h-[84px] w-[84px] shrink-0 items-center justify-center rounded-full {{ $disc }}">
            <div class="relative flex h-[68px] w-[68px] items-center justify-center">
                <svg class="absolute inset-0 {{ $mode === 'continue' ? '-rotate-90' : '' }} {{ $ring }}" width="68" height="68" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                    <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-opacity="{{ $mode === 'continue' && $progress === null ? 1 : 0.3 }}" stroke-width="2"/>
                    @if ($mode === 'continue' && $progress !== null)
                        <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-width="2" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ round($progress * 100) }} 100"/>
                    @endif
                </svg>
                <span class="flex h-[44px] w-[44px] items-center justify-center rounded-full font-heading text-[26px] font-[800] {{ $logo }}">Q</span>
            </div>
        </div>
@if ($mode === 'start')
    </button>
@else
    </a>
@endif
