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

    public bool $showQuestions = false;

    public bool $isNative = false;

    public int $arrivalRadius = 50;

    public bool $arrivedAtCurrent = false;

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

        $questResponse = $this->tryApiCall(fn () => $this->api->quests()->show($this->session['quest']['id'] ?? 0));
        $quest = $questResponse['data'] ?? [];

        $this->arrivalRadius = (int) ($quest['checkpoint_arrival_radius_meters'] ?? 50);

        $this->checkpoints = collect($quest['checkpoints'] ?? [])
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
        float $latitude = 0,
        float $longitude = 0,
        float $accuracy = 0,
        int $timestamp = 0,
        string $provider = '',
        string $error = '',
    ): void {
        if (! $success) {
            return;
        }

        $this->dispatch('player-moved', latitude: $latitude, longitude: $longitude, accuracy: $accuracy);

        if ($accuracy > 50) {
            $this->dispatch('gps-weak');
        }

        if ($this->arrivedAtCurrent || $this->showQuestions) {
            return;
        }

        $checkpoint = $this->checkpoints[$this->currentCheckpointIndex] ?? null;
        if (! $checkpoint || ! $checkpoint['latitude'] || ! $checkpoint['longitude']) {
            return;
        }

        $distanceKm = Quest::haversineDistance(
            $latitude, $longitude,
            (float) $checkpoint['latitude'], (float) $checkpoint['longitude'],
        );
        $distanceMeters = $distanceKm * 1000;

        $radius = $checkpoint['arrival_radius_override'] ?? $this->arrivalRadius;

        if ($distanceMeters <= $radius) {
            $this->arriveAtCheckpoint();
        }
    }

    public function arriveAtCheckpoint(): void
    {
        $checkpoint = $this->checkpoints[$this->currentCheckpointIndex] ?? null;
        if (! $checkpoint) {
            return;
        }

        $this->arrivedAtCurrent = true;

        $this->tryApiCall(fn () => $this->api->gameplay()->arrived(
            $this->code,
            $this->participantId,
            $checkpoint['id'],
            $checkpoint['latitude'] ?? 0,
            $checkpoint['longitude'] ?? 0,
        ));

        $this->showQuestions = true;
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

        $this->redirect('/session/' . $this->code . '/question/' . $checkpoint['id']);
    }

    public function loadLeaderboard(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->gameplay()->leaderboard($this->code));
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
    {{-- Map View --}}
    <div
        class="relative h-64 w-full bg-gray-200 dark:bg-gray-700"
        x-data="{
            map: null,
            userMarker: null,
            locationInterval: null,
            init() {
                if (typeof google === 'undefined') return;
                const checkpoints = @js($checkpoints);
                const current = checkpoints[{{ $currentCheckpointIndex }}];
                if (!current || !current.latitude) return;

                this.map = new google.maps.Map(this.$el, {
                    center: { lat: parseFloat(current.latitude), lng: parseFloat(current.longitude) },
                    zoom: 15,
                    mapTypeControl: false,
                    streetViewControl: false,
                });

                checkpoints.forEach((cp, i) => {
                    if (!cp.latitude || !cp.longitude) return;
                    new google.maps.Marker({
                        position: { lat: parseFloat(cp.latitude), lng: parseFloat(cp.longitude) },
                        map: this.map,
                        label: String(i + 1),
                        opacity: i === {{ $currentCheckpointIndex }} ? 1.0 : 0.4,
                    });
                });

                const isNative = @js($isNative);
                if (isNative) {
                    $wire.requestLocation();
                    this.locationInterval = setInterval(() => {
                        if (!$wire.showQuestions) $wire.requestLocation();
                    }, 4000);
                } else if (navigator.geolocation) {
                    navigator.geolocation.watchPosition((pos) => {
                        this.updateUserMarker(pos.coords.latitude, pos.coords.longitude);

                        if ($wire.arrivedAtCurrent || $wire.showQuestions) return;
                        const current = checkpoints[$wire.currentCheckpointIndex];
                        if (!current || !current.latitude) return;

                        const toRad = (d) => d * Math.PI / 180;
                        const R = 6371000;
                        const dLat = toRad(pos.coords.latitude - current.latitude);
                        const dLng = toRad(pos.coords.longitude - current.longitude);
                        const a = Math.sin(dLat/2)**2 + Math.cos(toRad(current.latitude)) * Math.cos(toRad(pos.coords.latitude)) * Math.sin(dLng/2)**2;
                        const dist = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                        const radius = current.arrival_radius_override || @js($arrivalRadius);

                        if (dist <= radius) {
                            $wire.arriveAtCheckpoint();
                        }
                    });
                }

                $wire.on('player-moved', (params) => {
                    const lat = params[0]?.latitude ?? params.latitude;
                    const lng = params[0]?.longitude ?? params.longitude;
                    if (lat && lng) this.updateUserMarker(lat, lng);
                });
            },
            updateUserMarker(lat, lng) {
                const userPos = { lat: parseFloat(lat), lng: parseFloat(lng) };
                if (this.userMarker) {
                    this.userMarker.setPosition(userPos);
                } else {
                    this.userMarker = new google.maps.Marker({
                        position: userPos,
                        map: this.map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: '#0B3D2E',
                            fillOpacity: 1,
                            strokeWeight: 2,
                            strokeColor: '#ffffff',
                        },
                    });
                }
            },
            destroy() {
                if (this.locationInterval) clearInterval(this.locationInterval);
            }
        }"
    >
        <div class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
            {{ __('general.loading') }}
        </div>
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

                <button wire:click="goToQuestions" class="mt-3 w-full rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                    {{ __('sessions.answer_questions') }}
                </button>
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
