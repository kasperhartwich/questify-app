<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use App\Models\Quest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Facades\Geolocation;
use Native\Mobile\Facades\System;

new
#[Title('Active Quest')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public string $code = '';

    public array $session = [];

    public int $participantId = 0;

    public int $currentCheckpointIndex = 0;

    public array $checkpoints = [];

    public array $leaderboard = [];

    public bool $showHint = false;

    public bool $isNative = false;

    public int $arrivalRadius = 50;

    /** True once the player's latest GPS fix is within the current checkpoint's radius. */
    public bool $withinRadius = false;

    /** Distance in metres from the player to the current checkpoint (null until first fix). */
    public ?int $distanceMeters = null;

    public ?float $playerLat = null;

    public ?float $playerLng = null;

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->isNative = System::isMobile();
        $this->participantId = session('questify_participant_id', 0);

        $response = $this->tryApiCall(fn () => $this->api->sessions()->show($code));
        $this->session = $response['data'] ?? [];

        // Auto-detect participant_id from session data if not in PHP session
        if (! $this->participantId && Auth::check()) {
            $participants = $this->session['participants'] ?? [];
            foreach ($participants as $p) {
                if (($p['user_id'] ?? null) === Auth::id()) {
                    $this->participantId = $p['id'];
                    session()->put('questify_participant_id', $p['id']);
                    break;
                }
            }
        }

        // Checkpoint coordinates come from the session (active-only, participant-scoped) — never
        // from the public quest detail endpoint, which must not leak the route (business rule 7).
        $this->arrivalRadius = (int) (data_get($this->session, 'quest.checkpoint_arrival_radius_meters') ?? 50);

        $this->checkpoints = collect($this->session['checkpoints'] ?? [])
            ->map(fn ($cp) => [
                'id' => $cp['id'],
                'title' => $cp['title'],
                'description' => $cp['description'] ?? '',
                'latitude' => $cp['latitude'] ?? null,
                'longitude' => $cp['longitude'] ?? null,
                'arrival_radius_override' => $cp['arrival_radius_override'] ?? null,
            ])
            ->toArray();

        $this->currentCheckpointIndex = session('questify_checkpoint_index', 0);
        $this->loadLeaderboard();
    }

    public function requestLocation(): void
    {
        Geolocation::getCurrentPosition(true);
    }

    #[OnNative(LocationReceived::class)]
    public function onLocationReceived(
        bool $success = false,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracy = null,
        ?int $timestamp = null,
        ?string $provider = null,
        ?string $error = null,
    ): void {
        // A successful fix can still arrive with null coordinates (geolocation v2); without
        // both we can neither update the map nor measure distance (haversine is typed float).
        if (! $success || $latitude === null || $longitude === null) {
            return;
        }

        $this->updatePlayerPosition($latitude, $longitude, $accuracy);
    }

    /**
     * Record the latest player position and recompute proximity to the current checkpoint.
     * Shared by the native LocationReceived event and the browser watchPosition fallback.
     */
    public function updatePlayerPosition(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $this->playerLat = $latitude;
        $this->playerLng = $longitude;

        // Remember the latest fix so the question screen can prove proximity to the server.
        session()->put('questify_player_lat', $latitude);
        session()->put('questify_player_lng', $longitude);

        $this->dispatch('player-moved', latitude: $latitude, longitude: $longitude, accuracy: $accuracy);

        if ($accuracy !== null && $accuracy > 50) {
            $this->dispatch('gps-weak');
        }

        $checkpoint = $this->checkpoints[$this->currentCheckpointIndex] ?? null;
        if (! $checkpoint || ! $checkpoint['latitude'] || ! $checkpoint['longitude']) {
            $this->withinRadius = false;
            $this->distanceMeters = null;

            return;
        }

        $this->distanceMeters = (int) round(Quest::haversineDistance(
            $latitude, $longitude,
            (float) $checkpoint['latitude'], (float) $checkpoint['longitude'],
        ) * 1000);

        $radius = $checkpoint['arrival_radius_override'] ?? $this->arrivalRadius;
        $this->withinRadius = $this->distanceMeters <= $radius;
    }

    public function showHint(): void
    {
        $this->showHint = true;
    }

    public function goToQuestions(): void
    {
        $checkpoint = $this->checkpoints[$this->currentCheckpointIndex] ?? null;
        if (! $checkpoint) {
            return;
        }

        // Proximity gate (spec §6): questions are only reachable once the player's GPS fix is
        // within the checkpoint's arrival radius. The server re-checks this on /arrived.
        if (! $this->withinRadius) {
            return;
        }

        $this->redirect('/session/' . $this->code . '/question/' . $checkpoint['id']);
    }

    public function loadLeaderboard(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->gameplay()->leaderboard($this->code));

        // The leaderboard endpoint returns a flat, score-ranked participant list for every play
        // mode, so "me" is always matched by participant id.
        $this->leaderboard = collect($response['data'] ?? [])
            ->take(5)
            ->map(fn ($p, $i) => [
                'rank' => $i + 1,
                'display_name' => $p['display_name'],
                'score' => $p['total_score'],
                'is_me' => $p['id'] === $this->participantId,
            ])
            ->toArray();
    }

    #[On('echo-presence:session.{code},LeaderboardUpdated')]
    public function onLeaderboardUpdated(): void
    {
        $this->loadLeaderboard();
    }

    public function getCurrentCheckpointProperty(): ?object
    {
        $cp = $this->checkpoints[$this->currentCheckpointIndex] ?? null;

        return $cp ? (object) $cp : null;
    }

    #[On('echo-presence:session.{code},SessionEnded')]
    public function onSessionEnded(): void
    {
        $this->redirect('/session/' . $this->code . '/complete');
    }
};
?>

<div class="flex flex-col">
    {{-- GPS Accuracy Warning --}}
    <div
        x-data="{ show: false, timeout: null }"
        x-on:gps-weak.window="show = true; clearTimeout(timeout); timeout = setTimeout(() => show = false, 5000)"
        x-show="show"
        x-transition
        x-cloak
        class="bg-amber-50 px-4 py-2 text-center text-xs font-medium text-amber-700"
    >
        {{ __('sessions.gps_weak') }}
    </div>

    {{-- Map View --}}
    <div
        class="relative h-64 w-full bg-gray-200 dark:bg-gray-700"
        x-data="{
            map: null,
            userMarker: null,
            locationInterval: null,
            init() {
                if (typeof L === 'undefined') return;
                try {
                    const checkpoints = @js($checkpoints);
                    const current = checkpoints[{{ $currentCheckpointIndex }}];
                    if (!current || !current.latitude) return;

                    this.map = L.map(this.$refs.activeMap, {
                        center: [parseFloat(current.latitude), parseFloat(current.longitude)],
                        zoom: 15,
                        attributionControl: false,
                        zoomControl: false,
                    });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.map);

                    checkpoints.forEach((cp, i) => {
                        if (!cp.latitude || !cp.longitude) return;
                        const isCurrent = i === {{ $currentCheckpointIndex }};
                        const icon = L.divIcon({
                            className: '',
                            html: '<div style=\'width:28px;height:28px;background:#0B3D2E;opacity:' + (isCurrent ? '1' : '0.4') + ';border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:bold;\'>' + (i + 1) + '</div>',
                            iconSize: [28, 28],
                            iconAnchor: [14, 14],
                        });
                        L.marker([parseFloat(cp.latitude), parseFloat(cp.longitude)], { icon: icon }).addTo(this.map);
                    });

                    const isNative = @js($isNative);
                    if (isNative) {
                        $wire.requestLocation();
                        this.locationInterval = setInterval(() => {
                            $wire.requestLocation();
                        }, 4000);
                    } else if (navigator.geolocation) {
                        // Browser fallback: forward each fix to the component, which recomputes
                        // distance and proximity server-side (same path as the native event).
                        navigator.geolocation.watchPosition((pos) => {
                            this.updateUserMarker(pos.coords.latitude, pos.coords.longitude);
                            $wire.updatePlayerPosition(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                        });
                    }

                    $wire.on('player-moved', (params) => {
                        const lat = params[0]?.latitude ?? params.latitude;
                        const lng = params[0]?.longitude ?? params.longitude;
                        if (lat && lng) this.updateUserMarker(lat, lng);
                    });
                } catch (e) { console.error('Active quest map init failed:', e); }
            },
            updateUserMarker(lat, lng) {
                const latLng = [parseFloat(lat), parseFloat(lng)];
                if (this.userMarker) {
                    this.userMarker.setLatLng(latLng);
                } else {
                    const icon = L.divIcon({
                        className: '',
                        html: '<div style=\'width:16px;height:16px;background:#0B3D2E;border:2px solid #fff;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,0.3)\'></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8],
                    });
                    this.userMarker = L.marker(latLng, { icon: icon }).addTo(this.map);
                }
            },
            destroy() {
                if (this.locationInterval) clearInterval(this.locationInterval);
                if (this.map) this.map.remove();
            }
        }"
    >
        <div x-ref="activeMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
    </div>

    <div class="flex-1 space-y-3 p-4">
        {{-- Current Checkpoint Info --}}
        @if ($this->currentCheckpoint)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wider text-forest-600 dark:text-forest-400">
                        {{ __('quests.checkpoint') }} {{ $currentCheckpointIndex + 1 }}/{{ count($checkpoints) }}
                    </span>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->currentCheckpoint->title }}</h2>
                @if ($this->currentCheckpoint->description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $this->currentCheckpoint->description }}</p>
                @endif

                @if ($withinRadius)
                    <button wire:click="goToQuestions" class="mt-3 w-full rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                        {{ __('sessions.answer_questions') }}
                    </button>
                @else
                    <div class="mt-3 w-full rounded-xl bg-gray-100 px-4 py-3 text-center dark:bg-gray-700">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                            @if ($distanceMeters !== null)
                                {{ $distanceMeters }} m {{ __('sessions.away') }} — {{ __('sessions.get_closer_to_answer') }}
                            @else
                                {{ __('sessions.navigating_to_checkpoint') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Leaderboard Strip --}}
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('sessions.leaderboard') }}</h3>
            <div class="space-y-1">
                @foreach ($leaderboard as $entry)
                    <div class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm {{ $entry['is_me'] ? 'bg-forest-50 font-semibold dark:bg-forest-900/20' : '' }}">
                        <span class="flex items-center gap-2">
                            <span class="w-5 text-center text-xs font-bold {{ $entry['rank'] <= 3 ? 'text-amber-500' : 'text-gray-400' }}">{{ $entry['rank'] }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $entry['display_name'] }}</span>
                            @if ($entry['is_me'])
                                <span class="text-xs text-forest-600 dark:text-forest-400">({{ __('sessions.you') }})</span>
                            @endif
                        </span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ number_format($entry['score']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
