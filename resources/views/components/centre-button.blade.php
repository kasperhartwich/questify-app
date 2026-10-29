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

    public function mount(): void
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

<a
    href="{{ $href }}"
    wire:navigate.hover
    data-centre-mode="{{ $mode }}"
    class="flex flex-[2.6] items-center justify-center px-1"
>
    @if ($mode === 'continue')
        <div class="flex h-[48px] w-full min-w-0 items-center gap-2.5 rounded-full border border-cream-border bg-cream px-[5px] shadow-[0_3px_12px_rgba(11,61,46,0.18)]">
            <div class="relative flex h-[36px] w-[36px] shrink-0 items-center justify-center">
                <svg class="absolute inset-0 -rotate-90 text-amber-400" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                    <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-opacity="{{ $progress === null ? 1 : 0.25 }}" stroke-width="3"/>
                    @if ($progress !== null)
                        <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-width="3" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ round($progress * 100) }} 100"/>
                    @endif
                </svg>
                <span class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-amber-400 font-heading text-[13px] font-[800] text-forest-600">Q</span>
            </div>
            <span class="min-w-0 flex-1 truncate text-[14px] font-bold text-bark">{{ __('general.centre_continue') }}</span>
            <svg class="shrink-0 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,6 15,12 9,18"/></svg>
        </div>
    @else
        <div class="flex h-[48px] w-full min-w-0 items-center gap-2.5 rounded-full bg-forest-600 px-[6px] shadow-[0_3px_12px_rgba(11,61,46,0.35)]">
            <div class="relative flex h-[36px] w-[36px] shrink-0 items-center justify-center">
                <svg class="absolute inset-0 text-amber-400" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                    <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>
                </svg>
                <span class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-amber-400 font-heading text-[13px] font-[800] text-forest-600">Q</span>
            </div>
            <span class="min-w-0 flex-1 truncate text-[14px] font-bold text-white">{{ __('general.centre_join') }}</span>
            <svg class="shrink-0 text-white" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,6 15,12 9,18"/></svg>
        </div>
    @endif
</a>
