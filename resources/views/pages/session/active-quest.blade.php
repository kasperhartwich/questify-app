<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\RequestsLocation;
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
    use HandlesApiErrors, RequestsLocation, WithApiClient;

    public string $code = '';

    public array $session = [];

    public int $participantId = 0;

    public int $currentCheckpointIndex = 0;

    public array $checkpoints = [];

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

        $response = $this->tryApiCall(fn () => $this->api->sessions()->show($code, $this->participantId));
        $this->session = $response['data'] ?? [];

        $participants = $this->session['participants'] ?? [];
        $participantIds = array_column($participants, 'id');

        // Discard a stale participant id left over from a previous session — otherwise answers
        // would be misattributed to a participant that isn't part of THIS session.
        if ($this->participantId && ! in_array($this->participantId, $participantIds)) {
            $this->participantId = 0;
            session()->forget('questify_participant_id');
        }

        // Auto-detect participant_id from session data if not in PHP session
        if (! $this->participantId && Auth::check()) {
            foreach ($participants as $p) {
                if (($p['user_id'] ?? null) === Auth::id()) {
                    $this->participantId = $p['id'];
                    session()->put('questify_participant_id', $p['id']);
                    break;
                }
            }

            // The first response was for an unknown player, so it holds no coordinates.
            if ($this->participantId) {
                $response = $this->tryApiCall(fn () => $this->api->sessions()->show($code, $this->participantId));
                $this->session = $response['data'] ?? $this->session;
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

        // The raw response is public state too: drop its copy of every stop's position.
        unset($this->session['checkpoints']);

        // Progress is kept per session: one shared index carried a finished
        // quest's position into the next one, pointing past its last stop.
        $this->currentCheckpointIndex = min(
            (int) session('questify_checkpoint_index.' . $this->code, 0),
            max(count($this->checkpoints) - 1, 0),
        );

        // Livewire ships public properties to the browser, so a later stop's position is blanked
        // out of the data itself, not just left off the map: the player must not see where it is.
        foreach ($this->checkpoints as $i => $checkpoint) {
            if ($i > $this->currentCheckpointIndex) {
                $this->checkpoints[$i]['latitude'] = null;
                $this->checkpoints[$i]['longitude'] = null;
            }
        }
    }

    protected function wantsFineLocation(): bool
    {
        return true;
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

    public function getCurrentCheckpointProperty(): ?object
    {
        $cp = $this->checkpoints[$this->currentCheckpointIndex] ?? null;

        return $cp ? (object) $cp : null;
    }

    #[On('echo:session.{code},SessionEnded')]
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
        class="relative h-64 w-full bg-gray-200"
        x-data="{
            map: null,
            userMarker: null,
            locationInterval: null,
            autoZoom: null,
            checkpointLatLng: null,
            arrivalRadius: @js($arrivalRadius),
            paused: false,
            pauseTimer: null,
            programmatic: false,
            radiusCircle: null,
            init() {
                if (typeof L === 'undefined') return;
                try {
                    const checkpoints = @js($checkpoints);
                    const current = checkpoints[{{ $currentCheckpointIndex }}];
                    if (!current || !current.latitude) return;

                    this.map = L.map(this.$refs.activeMap, {
                        center: [parseFloat(current.latitude), parseFloat(current.longitude)],
                        zoom: MapZoom.FALLBACK_ZOOM,
                        attributionControl: false,
                        zoomControl: false,
                        zoomSnap: 0,
                    });
                    this.checkpointLatLng = [parseFloat(current.latitude), parseFloat(current.longitude)];
                    this.arrivalRadius = current.arrival_radius_override ?? this.arrivalRadius;
                    this.autoZoom = new MapZoom.AutoZoom(MapZoom.FALLBACK_ZOOM);
                    // A drag or pinch by the player pauses auto-zoom; our own flyTo/panTo must not.
                    this.map.on('dragstart', () => this.pauseAutoZoom());
                    this.map.on('zoomstart', () => { if (!this.programmatic) this.pauseAutoZoom(); });
                    this.map.on('moveend', () => { this.programmatic = false; });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.map);

                    // Re-measure once painted: Leaflet caches the container size
                    // at construction, before the layout has settled.
                    requestAnimationFrame(() => this.map.invalidateSize());
                    if (window.ResizeObserver) {
                        new ResizeObserver(() => this.map.invalidateSize())
                            .observe(this.$refs.activeMap);
                    }

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
                        const accuracy = params[0]?.accuracy ?? params.accuracy ?? null;
                        if (lat && lng) {
                            this.updateUserMarker(lat, lng);
                            this.followPlayer(parseFloat(lat), parseFloat(lng), accuracy);
                        }
                    });
                } catch (e) { console.error('Active quest map init failed:', e); }
            },
            followPlayer(lat, lng, accuracy) {
                if (!this.map || !this.autoZoom || this.autoZoom.isPaused(Date.now())) return;
                const size = this.map.getSize();
                const [cpLat, cpLng] = this.checkpointLatLng;
                const distance = MapZoom.haversineMeters(lat, lng, cpLat, cpLng);
                const zoom = this.autoZoom.update({
                    distance,
                    accuracy: accuracy === null ? null : parseFloat(accuracy),
                    viewportPx: Math.min(size.x, size.y),
                    radius: this.arrivalRadius,
                    lat: cpLat,
                    now: Date.now(),
                });
                // Close in, the checkpoint is the centre; otherwise frame both points.
                const center = distance <= MapZoom.NEAR_METERS
                    ? [cpLat, cpLng]
                    : [(lat + cpLat) / 2, (lng + cpLng) / 2];
                // Inside 30 m, show where the arrival radius ends.
                if (distance <= MapZoom.NEAR_METERS && !this.radiusCircle) {
                    this.radiusCircle = L.circle(this.checkpointLatLng, {
                        radius: this.arrivalRadius,
                        color: '#0B3D2E',
                        weight: 2,
                        fillOpacity: 0.1,
                    }).addTo(this.map);
                } else if (distance > MapZoom.NEAR_RELEASE_METERS && this.radiusCircle) {
                    this.radiusCircle.remove();
                    this.radiusCircle = null;
                }
                this.programmatic = true;
                setTimeout(() => { this.programmatic = false; }, 1500);
                if (zoom !== null) {
                    this.map.flyTo(center, zoom, { duration: MapZoom.ANIMATION_SECONDS });
                } else {
                    this.map.panTo(center, { animate: true, duration: MapZoom.ANIMATION_SECONDS });
                }
            },
            pauseAutoZoom() {
                if (!this.autoZoom) return;
                this.autoZoom.pause(Date.now());
                this.paused = true;
                clearTimeout(this.pauseTimer);
                this.pauseTimer = setTimeout(() => { this.paused = false; }, MapZoom.PAUSE_MS);
            },
            recenter() {
                if (!this.autoZoom) return;
                clearTimeout(this.pauseTimer);
                this.paused = false;
                this.autoZoom.resume();
                if (this.userMarker) {
                    const p = this.userMarker.getLatLng();
                    this.followPlayer(p.lat, p.lng, null);
                } else {
                    this.map.flyTo(this.checkpointLatLng, MapZoom.FALLBACK_ZOOM, { duration: MapZoom.ANIMATION_SECONDS });
                }
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
                clearTimeout(this.pauseTimer);
                if (this.map) this.map.remove();
            }
        }"
    >
        <div x-ref="activeMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
        <button
            type="button"
            x-show="paused"
            x-cloak
            x-on:click="recenter()"
            class="absolute bottom-3 right-3 z-[1000] rounded-full bg-white px-4 py-2 text-sm font-semibold text-bark shadow-md"
        >{{ __('sessions.recenter_map') }}</button>
    </div>

    <div class="flex-1 space-y-3 p-4">
        {{-- Current Checkpoint Info --}}
        @if ($this->currentCheckpoint)
            <div class="rounded-[14px] border-[1.5px] border-cream-border bg-white p-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wider text-forest-600">
                        {{ __('quests.checkpoint') }} {{ $currentCheckpointIndex + 1 }}/{{ count($checkpoints) }}
                    </span>
                </div>
                <h2 class="font-heading text-[18px] font-extrabold text-bark">{{ $this->currentCheckpoint->title }}</h2>
                @if ($this->currentCheckpoint->description)
                    <p class="mt-1 text-sm text-muted">{{ $this->currentCheckpoint->description }}</p>
                @endif

                @if ($withinRadius)
                    <button wire:click="goToQuestions" class="mt-3 w-full rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                        {{ __('sessions.answer_questions') }}
                    </button>
                @else
                    <div class="mt-3 w-full rounded-xl bg-cream-dark px-4 py-3 text-center">
                        <p class="text-sm font-semibold text-muted">
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
    </div>
</div>
