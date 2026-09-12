<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\RequestsLocation;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Facades\Geolocation;
use Native\Mobile\Facades\System;

new
#[Title('Quest Map')]
#[\Livewire\Attributes\Layout('layouts.app', self::LAYOUT_PARAMS)]
class extends Component
{
    use HandlesApiErrors, RequestsLocation, WithApiClient;

    /**
     * Kept as a constant so the `#[Layout]` attribute contains no inline array —
     * Livewire's single-file-component detector rejects `]` between `new` and `class`.
     *
     * @var array<string, bool>
     */
    public const LAYOUT_PARAMS = ['fullscreen' => true, 'skipSafeAreaTop' => true];

    /** @var array<int, array{id: int, title: string, latitude: float, longitude: float, distance_to_farthest_km: float}> */
    public array $pins = [];

    /** Quest id of the pin the player tapped, or null when nothing is selected. */
    public ?int $selectedPinId = null;

    /**
     * The selected pin, straight from $pins.
     *
     * @return array<string, mixed>|null
     */
    public function getSelectedPinProperty(): ?array
    {
        if ($this->selectedPinId === null) {
            return null;
        }

        return collect($this->pins)->firstWhere('id', $this->selectedPinId);
    }

    public float $latitude = 55.6761;

    public float $longitude = 12.5683;

    public bool $isNative = false;

    public function mount(): void
    {
        $this->isNative = System::isMobile();
        $this->loadPins();

        // /discover/map?pin=12 opens with that quest's card already showing,
        // so a shared link lands on the quest rather than a bare map.
        if ($pin = request()->integer('pin')) {
            $this->selectedPinId = $pin;
        }
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
        if (! $success) {
            return;
        }

        // A successful fix can still carry null coordinates (geolocation v2); the $latitude /
        // $longitude properties are typed float, so assigning null would throw a TypeError.
        if ($latitude === null || $longitude === null) {
            return;
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->loadPins();
        $this->dispatch('native-location', latitude: $latitude, longitude: $longitude);
    }

    public function loadNearby(float $latitude, float $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->loadPins();
    }

    public function loadPins(): void
    {
        try {
            $response = $this->api->quests()->nearby(
                $this->latitude,
                $this->longitude,
                ['radius' => 50],
            );
        } catch (ApiException) {
            $response = null;
        }

        if (! empty($response['data'])) {
            $this->pins = collect($response['data'])
                ->filter(fn ($quest) => ! empty($quest['starting_checkpoint']['latitude']))
                ->map(fn ($quest) => [
                    'id' => $quest['id'],
                    'title' => $quest['title'] ?? '',
                    'difficulty' => $quest['difficulty'] ?? '',
                    'category' => $quest['category']['name'] ?? null,
                    'visibility' => $quest['visibility'] ?? null,
                    'latitude' => (float) $quest['starting_checkpoint']['latitude'],
                    'longitude' => (float) $quest['starting_checkpoint']['longitude'],
                    'distance_to_start_km' => (float) ($quest['distance_to_start_km'] ?? 0),
                    'distance_to_farthest_km' => (float) ($quest['distance_to_farthest_km'] ?? 0),
                    'checkpoint_count' => (int) ($quest['checkpoint_count'] ?? 0),
                ])
                ->all();

            return;
        }

        // Fallback to generic list if nearby endpoint is unavailable
        $response = $this->tryApiCall(fn () => $this->api->quests()->list()) ?? ['data' => []];

        $this->pins = collect($response['data'] ?? [])
            ->filter(fn ($quest) => ! empty($quest['checkpoints'][0]['latitude']))
            ->map(fn ($quest) => [
                'id' => $quest['id'],
                'title' => $quest['title'] ?? '',
                'difficulty' => $quest['difficulty'] ?? '',
                'category' => $quest['category']['name'] ?? null,
                'visibility' => $quest['visibility'] ?? null,
                'latitude' => (float) $quest['checkpoints'][0]['latitude'],
                'longitude' => (float) $quest['checkpoints'][0]['longitude'],
                'distance_to_start_km' => 0,
                'distance_to_farthest_km' => 0,
                'checkpoint_count' => count($quest['checkpoints'] ?? []),
            ])
            ->all();
    }
};
?>

<div class="fixed inset-0 flex flex-col bg-[#E4EDE4]"
    x-data="{
        map: null,
        markers: [],
        circleLayer: null,
        userMarker: null,
        pins: @js($pins),
        visibleCount: 0,
        selectedPin: null,
        searchQuery: '',
        filterDifficulty: 'all',
        isSatellite: false,
        userLocated: false,
        streetsLayer: null,
        satelliteLayer: null,
        init() {
            // Clear native EDGE bottom nav via JS bridge
            fetch('/_native/api/call', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ method: 'Edge.Set', params: { components: [] } })
            }).catch(() => {});
            // Also hide HTML tab bar as fallback
            const hideTab = () => {
                const el = document.getElementById('app-tab-bar');
                if (el) { el.style.display = 'none'; return true; }
                return false;
            };
            if (!hideTab()) {
                const iv = setInterval(() => { if (hideTab()) clearInterval(iv); }, 50);
                setTimeout(() => clearInterval(iv), 5000);
            }
            document.addEventListener('livewire:navigating', () => {
                const el = document.getElementById('app-tab-bar');
                if (el) el.style.display = '';
            }, { once: true });
            const boot = () => {
                if (typeof L === 'undefined') {
                    setTimeout(boot, 50);
                    return;
                }
                try {
                    this.map = L.map(this.$refs.mapCanvas, {
                        center: [55.6761, 12.5683],
                        zoom: 14,
                        attributionControl: false,
                        zoomControl: false,
                    });
                    this.streetsLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
                    this.satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
                    this.streetsLayer.addTo(this.map);
                    L.control.attribution({ position: 'bottomleft', prefix: false }).addTo(this.map);
                    // Force map to recalculate size (fixes blank map on wire:navigate)
                    setTimeout(() => this.map.invalidateSize(), 100);
                    this.addMarkers();
                    this.updateVisibleCount();
                    this.map.on('moveend zoomend', () => this.updateVisibleCount());
                    this.locateUser();
                } catch (e) {
                    console.error('Map init failed:', e);
                    return;
                }
            };
            boot();
            $wire.on('native-location', (params) => {
                const lat = params[0]?.latitude ?? params.latitude;
                const lng = params[0]?.longitude ?? params.longitude;
                if (!lat || !lng) return;

                // Two steps closer than the old default: landing at 13 showed
                // half the city rather than where you are standing.
                this.map.flyTo([lat, lng], 15);
                this.showUserPosition(lat, lng);

                if (!this.userLocated) {
                    this.userLocated = true;
                }
                this.pins = $wire.pins;
                this.addMarkers();
                this.updateVisibleCount();
            });
        },
        showUserPosition(lat, lng) {
            if (!this.map) return;

            if (this.userMarker) {
                this.userMarker.setLatLng([lat, lng]);
                return;
            }

            this.userMarker = L.marker([lat, lng], {
                icon: L.divIcon({ className: '', html: '<div class=\'leaflet-user-dot\'></div>', iconSize: [18, 18], iconAnchor: [9, 9] }),
                interactive: false,
                zIndexOffset: 1000,
            }).addTo(this.map);
        },
        addMarkers() {
            this.markers.forEach(m => this.map.removeLayer(m));
            this.markers = [];
            this.removeCircle();
            const colorClass = (d) => d === 'easy' ? 'marker-easy' : d === 'medium' ? 'marker-medium' : d === 'hard' ? 'marker-hard' : 'marker-default';
            this.pins.forEach((pin) => {
                const icon = L.divIcon({
                    className: '',
                    html: '<div class=\'leaflet-quest-marker ' + colorClass(pin.difficulty) + '\'><span class=\'leaflet-quest-marker-num\'>' + (pin.checkpoint_count || '') + '</span></div>',
                    iconSize: [30, 30],
                    iconAnchor: [8, 30],
                });
                const marker = L.marker([pin.latitude, pin.longitude], { icon: icon }).addTo(this.map);
                marker.on('click', () => {
                    this.selectedPin = pin;
                    $wire.set('selectedPinId', pin.id);
                    this.map.flyTo([pin.latitude, pin.longitude], 15);
                });
                this.markers.push(marker);
            });
        },
        updateVisibleCount() {
            if (!this.map) return;
            const bounds = this.map.getBounds();
            this.visibleCount = this.pins.filter(p => bounds.contains([p.latitude, p.longitude])).length;
        },
        removeCircle() {
            if (this.circleLayer) {
                this.map.removeLayer(this.circleLayer);
                this.circleLayer = null;
            }
        },
        drawCircle(pin) {
            this.removeCircle();
            if (!pin.distance_to_farthest_km || pin.distance_to_farthest_km <= 0) return;
            this.circleLayer = L.circle([pin.latitude, pin.longitude], {
                radius: pin.distance_to_farthest_km * 1000,
                color: '#0B3D2E',
                weight: 2,
                opacity: 0.4,
                dashArray: '8,5',
                fillColor: '#0B3D2E',
                fillOpacity: 0.08,
            }).addTo(this.map);
        },
        toggleStyle() {
            this.isSatellite = !this.isSatellite;
            if (this.isSatellite) {
                this.map.removeLayer(this.streetsLayer);
                this.satelliteLayer.addTo(this.map);
            } else {
                this.map.removeLayer(this.satelliteLayer);
                this.streetsLayer.addTo(this.map);
            }
        },
        locateUser() {
            if (!this.map) return;
            $wire.requestLocation();
        },
        searchArea() {
            if (!this.searchQuery.trim() || !this.map) return;
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(this.searchQuery) + '&limit=1')
                .then(r => r.json())
                .then(results => {
                    if (results.length > 0) {
                        const lat = parseFloat(results[0].lat);
                        const lng = parseFloat(results[0].lon);
                        this.map.flyTo([lat, lng], 14);
                        $wire.loadNearby(lat, lng).then(() => {
                            this.pins = $wire.pins;
                            this.addMarkers();
                            this.updateVisibleCount();
                        });
                    }
                })
                .catch(() => {});
        },
    }"
>
    <style>
        .leaflet-quest-marker {
            width: 30px;
            height: 30px;
            border: 2.5px solid white;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .leaflet-user-dot {
            width: 18px; height: 18px; border-radius: 50%;
            background: #1565C0; border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(21,101,192,0.25), 0 2px 6px rgba(0,0,0,0.3);
        }
        .leaflet-quest-marker-num {
            transform: rotate(45deg);
            font-family: 'Exo 2', sans-serif;
            font-size: 11px;
            font-weight: 800;
            color: white;
        }
        .marker-easy { background-color: #0B3D2E; }
        .marker-medium { background-color: #E5A117; }
        .marker-hard { background-color: #E85C3A; }
        .marker-default { background-color: #7C3AED; }
    </style>

    {{-- Full-screen map --}}
    <div x-ref="mapCanvas" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0;"
         @click="if (!$event.target.closest('.leaflet-quest-marker')) { selectedPin = null; $wire.set('selectedPinId', null); removeCircle(); }"></div>

    {{-- Floating search bar --}}
    <div class="absolute left-0 right-0 z-[1000] px-4" style="top: calc(env(safe-area-inset-top, 0px) + 10px);">
        <div class="flex gap-2">
            <a href="/discover/list" class="flex h-[44px] w-[36px] shrink-0 items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]" wire:navigate>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <form @submit.prevent="searchArea()" class="flex flex-1 items-center gap-2 rounded-[12px] bg-white px-[14px] py-[12px] shadow-[0_2px_10px_rgba(0,0,0,0.15)]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <input x-model="searchQuery" type="text" placeholder="{{ __('general.search_area') }}..." class="flex-1 border-0 bg-transparent p-0 text-[14px] text-bark placeholder-[#B0A898] outline-none focus:ring-0" />
            </form>
        </div>
        {{-- Filter chips (disabled for now) --}}
    </div>

    {{-- Floating buttons (bottom right) --}}
    <div class="absolute right-4 z-[1000] flex flex-col gap-2" style="bottom: calc(env(safe-area-inset-bottom, 0px) + {{ $this->selectedPin ? '250px' : '90px' }});">
        {{-- Expand button (only when quest selected) --}}
        @if ($this->selectedPin)
            <a href="/quests/{{ $this->selectedPin['id'] }}" class="flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]" wire:navigate>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
            </a>
        @endif
        {{-- My location button --}}
        <button
            @click="locateUser()"
            class="flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
        </button>
    </div>

    {{-- Bottom: quest card (only when selected) --}}
    <div class="absolute inset-x-0 z-[1000] px-4 transition-all duration-300" style="bottom: calc(env(safe-area-inset-bottom, 0px) + 16px);">
        @if ($this->selectedPin)
            @php($pin = $this->selectedPin)
            <div class="overflow-hidden rounded-[18px] bg-white shadow-[0_4px_20px_rgba(0,0,0,0.15)]" wire:key="pin-{{ $pin['id'] }}">
                <div class="flex items-start gap-3 px-4 pb-3 pt-4">
                    <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-full bg-[#E8F5E9]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5" fill="#0B3D2E" stroke="none"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-heading text-[15px] font-bold leading-tight text-bark">{{ $pin['title'] }}</h3>
                        @if (($pin['distance_to_start_km'] ?? 0) > 0)
                            <p class="mt-0.5 text-[12px] text-muted">{{ number_format($pin['distance_to_start_km'], 1) }} km {{ __('general.away') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-[6px] px-4 pb-3">
                    <span class="rounded-full px-2.5 py-[3px] text-[11px] font-semibold
                        @class([
                            'bg-[#D4EDE4] text-forest-600' => $pin['difficulty'] === 'easy',
                            'bg-red-50 text-coral' => $pin['difficulty'] === 'hard',
                            'bg-amber-100 text-amber-700' => ! in_array($pin['difficulty'], ['easy', 'hard'], true),
                        ])">{{ __('general.'.$pin['difficulty']) }}</span>
                    @if ($pin['category'] ?? null)
                        <span class="rounded-full bg-amber-100 px-2.5 py-[3px] text-[11px] font-semibold text-amber-700">{{ $pin['category'] }}</span>
                    @endif
                    @if ($pin['visibility'] ?? null)
                        <span class="rounded-full bg-[#EEF2FF] px-2.5 py-[3px] text-[11px] font-semibold text-[#4055A8]">{{ __('general.'.$pin['visibility']) }}</span>
                    @endif
                    <span class="rounded-full bg-cream px-2.5 py-[3px] text-[11px] font-semibold text-muted">{{ $pin['checkpoint_count'] }} {{ __('general.stops') }}</span>
                    @if (($pin['distance_to_farthest_km'] ?? 0) > 0)
                        <span class="rounded-full bg-cream px-2.5 py-[3px] text-[11px] font-semibold text-muted">{{ number_format($pin['distance_to_farthest_km'], 1) }} km</span>
                    @endif
                </div>

                <div class="px-4 pb-4">
                    <a href="/quests/{{ $pin['id'] }}" class="block rounded-[12px] bg-forest-600 py-[13px] text-center text-[14px] font-bold text-white" wire:navigate>{{ __('general.view_quest') }} &rarr;</a>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    // Hide tab bar on map page
    const hideTab = () => {
        const el = document.getElementById('app-tab-bar');
        if (el) { el.style.display = 'none'; return true; }
        return false;
    };
    const iv = setInterval(() => { if (hideTab()) clearInterval(iv); }, 50);
    setTimeout(() => clearInterval(iv), 5000);
    document.addEventListener('livewire:navigating', () => {
        clearInterval(iv);
        const el = document.getElementById('app-tab-bar');
        if (el) el.style.display = '';
    }, { once: true });
</script>
@endscript
