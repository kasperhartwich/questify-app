<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\WithApiClient;
use App\Services\TokenStorage;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Events\Geolocation\PermissionStatusReceived;
use Native\Mobile\Facades\Geolocation;

new class extends Component
{
    use WithApiClient;

    /** The arrival distance the quest screen falls back on, in metres. */
    private const AT_START_METERS = 30;

    /** Km around the player to ask the nearby call about: just past the arrival distance. */
    private const AT_START_SEARCH_KM = 0.1;

    public string $mode = 'join';

    public string $href = '/join';

    public ?float $progress = null;

    /** A quest is waiting for the player while they are on a screen outside its session. */
    public bool $pulse = false;

    /** The quest whose start the player stands at, while they are not playing. */
    public ?int $atStartQuestId = null;

    public string $atStartQuestTitle = '';

    public ?int $questId = null;

    public function mount(bool $onQuestScreen = false, bool $onSessionScreen = false, ?int $questId = null): void
    {
        $this->questId = $questId;

        $this->useActiveParticipationIfSignedIn();

        if ($this->mode === 'join' && $onQuestScreen) {
            $this->mode = 'start';
        }

        $this->pulse = $this->mode === 'continue' && ! $onSessionScreen;

        $this->checkLocationWhenAllowed();
    }

    /**
     * Looks at the player's position only when location is already allowed:
     * the bar is on every screen and must never raise the system prompt.
     */
    private function checkLocationWhenAllowed(): void
    {
        if ($this->mode === 'continue' || ! TokenStorage::has()) {
            return;
        }

        try {
            Geolocation::checkPermissions()->get();
        } catch (\Throwable) {
            // Not running on a native device: there is no position to read.
        }
    }

    #[OnNative(PermissionStatusReceived::class)]
    public function onLocationPermissionStatus(string $location = ''): void
    {
        if ($location !== 'granted' || $this->mode === 'continue') {
            return;
        }

        Geolocation::getCurrentPosition(false);
    }

    #[OnNative(LocationReceived::class)]
    public function onLocationReceived(bool $success = false, ?float $latitude = null, ?float $longitude = null): void
    {
        if (! $success || $latitude === null || $longitude === null || $this->mode === 'continue' || ! TokenStorage::has()) {
            return;
        }

        try {
            $response = $this->api->quests()->nearby($latitude, $longitude, ['radius' => self::AT_START_SEARCH_KM]);
        } catch (ApiException) {
            return;
        }

        $this->useQuestAtStart($response['data'] ?? []);
    }

    /**
     * Remembers the closest quest whose start is within the arrival distance.
     *
     * @param  array<int|string, mixed>  $quests
     */
    private function useQuestAtStart(array $quests): void
    {
        $this->atStartQuestId = null;
        $this->atStartQuestTitle = '';

        $closest = collect($quests)
            ->filter(fn (mixed $quest): bool => is_array($quest) && isset($quest['id'], $quest['distance_to_start_km']))
            ->filter(fn (array $quest): bool => (float) $quest['distance_to_start_km'] * 1000 <= self::AT_START_METERS)
            ->sortBy(fn (array $quest): float => (float) $quest['distance_to_start_km'])
            ->first();

        if ($closest === null) {
            return;
        }

        $this->atStartQuestId = (int) $closest['id'];
        $this->atStartQuestTitle = (string) ($closest['title'] ?? '');
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
    // Green: the player stands at a quest's start. On a quest screen only when it is that quest.
    $atStart = $atStartQuestId !== null
        && ($mode === 'join' || ($mode === 'start' && $atStartQuestId === $questId));
    $label = match (true) {
        $atStart => __('general.centre_start_quest', ['title' => $atStartQuestTitle]),
        $mode === 'start' => __('general.centre_start'),
        $mode === 'continue' => __('general.centre_continue'),
        default => __('general.centre_join'),
    };
    $disc = match (true) {
        $mode === 'continue' => 'border border-cream-border bg-cream shadow-[0_6px_18px_rgba(11,61,46,0.28)]',
        $atStart => 'bg-forest-600 shadow-[0_0_0_8px_rgba(179,212,198,0.6),0_6px_18px_rgba(11,61,46,0.4)]',
        default => 'bg-amber-400 shadow-[0_0_0_8px_rgba(252,232,189,0.6),0_6px_18px_rgba(245,166,35,0.4)]',
    };
    $letter = $atStart ? 'text-amber-400' : 'text-forest-600';
    $target = $mode === 'join' && $atStart ? route('discover.quest', $atStartQuestId, false) : $href;
@endphp

@if ($mode === 'start')
    <button
        type="button"
        wire:click="startQuest"
        data-centre-mode="start"
        data-centre-at-start="{{ $atStart ? 'yes' : 'no' }}"
        aria-label="{{ $label }}"
        class="relative z-10 flex w-[84px] shrink-0 items-center justify-center"
    >
@else
    <a
        href="{{ $target }}"
        wire:navigate.hover
        data-centre-mode="{{ $mode }}"
        data-centre-at-start="{{ $atStart ? 'yes' : 'no' }}"
        aria-label="{{ $label }}"
        class="relative z-10 flex w-[84px] shrink-0 items-center justify-center"
    >
@endif
        <div class="relative -mt-7 flex h-[72px] w-[72px] shrink-0 items-center justify-center rounded-full {{ $disc }}">
            <span data-centre-pulse="{{ $pulse ? 'on' : 'off' }}" class="pointer-events-none absolute inset-0 rounded-full border-[3px] border-amber-400 {{ $pulse ? 'motion-safe:animate-centre-pulse motion-reduce:opacity-40' : 'hidden' }}" aria-hidden="true"></span>
            <div class="relative flex h-[58px] w-[58px] items-center justify-center">
                @if ($mode === 'continue')
                    <svg class="absolute inset-0 -rotate-90 text-amber-400" width="58" height="58" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                        <circle class="text-cream-border" cx="18" cy="18" r="16" stroke="currentColor" stroke-width="2"/>
                        @if ($progress > 0)
                            <circle cx="18" cy="18" r="16" stroke="currentColor" stroke-width="2" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ round($progress * 100) }} 100"/>
                        @endif
                    </svg>
                @endif
                <span class="flex h-[38px] w-[38px] items-center justify-center rounded-full font-heading text-[22px] font-[800] {{ $letter }}">Q</span>
            </div>
        </div>
@if ($mode === 'start')
    </button>
@else
    </a>
@endif
