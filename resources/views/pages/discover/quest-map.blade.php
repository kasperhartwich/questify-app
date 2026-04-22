<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Edge\Edge;
use Native\Mobile\Facades\Geolocation;
use Native\Mobile\Facades\System;

new
#[Title('Quest Map')]
#[\Livewire\Attributes\Layout('layouts.app', ['fullscreen' => true, 'skipSafeAreaTop' => true])]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    /** @var array<int, array{id: int, title: string, latitude: float, longitude: float, distance_to_farthest_km: float}> */
    public array $pins = [];

    public float $latitude = 55.6761;

    public float $longitude = 12.5683;

    public bool $isNative = false;

    public function mount(): void
    {
        $this->isNative = System::isMobile();
        $this->loadPins();
    }


    public function requestLocation(): void
    {
        Geolocation::getCurrentPosition();
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
        pins: @js($pins),
        visibleCount: 0,
        selectedPin: null,
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
                this.map.flyTo([lat, lng], 13);
                if (!this.userLocated) {
                    this.userLocated = true;
                }
                this.pins = $wire.pins;
                this.addMarkers();
                this.updateVisibleCount();
            });
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
            const isNative = @js($isNative);
            if (isNative) {
                $wire.requestLocation();
            } else if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.map.flyTo([lat, lng], 13);
                    if (!this.userLocated) {
                        this.userLocated = true;
                        $wire.loadNearby(lat, lng).then(() => {
                            this.pins = $wire.pins;
                            this.addMarkers();
                        });
                    }
                });
            }
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
        .leaflet-quest-marker-num {
            transform: rotate(45deg);
            font-family: 'Exo 2', sans-serif;
            font-size: 11px;
            font-weight: 800;
            color: white;
        }
        .marker-easy { background-color: #E5A117; }
        .marker-medium { background-color: #0B3D2E; }
        .marker-hard { background-color: #E85C3A; }
        .marker-default { background-color: #7C3AED; }
    </style>

    {{-- Full-screen map --}}
    <div x-ref="mapCanvas" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0;"
         @click="if (!$event.target.closest('.leaflet-quest-marker')) { selectedPin = null; removeCircle(); }"></div>

    {{-- Floating search bar --}}
    <div class="absolute left-0 right-0 z-[1000] px-4" style="top: calc(env(safe-area-inset-top, 0px) + 10px);">
        <div class="flex gap-2">
            <a href="/discover/list" class="flex h-[44px] w-[36px] shrink-0 items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]" wire:navigate>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="flex flex-1 items-center gap-2 rounded-[12px] bg-white px-[14px] py-[12px] shadow-[0_2px_10px_rgba(0,0,0,0.15)]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <span class="text-[14px] text-[#B0A898]">{{ __('general.search_area') }}...</span>
            </div>
        </div>
        {{-- Filter chips (disabled for now) --}}
    </div>

    {{-- Floating buttons (bottom right) --}}
    <div class="absolute right-4 z-[1000] flex flex-col gap-2" style="bottom: calc(env(safe-area-inset-bottom, 0px) + 90px);">
        {{-- Expand button (only when quest selected) --}}
        <template x-if="selectedPin">
            <a :href="'/quests/' + selectedPin.id" class="flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]" wire:navigate>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
            </a>
        </template>
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
        <template x-if="selectedPin">
            <div class="overflow-hidden rounded-[18px] bg-white shadow-[0_4px_20px_rgba(0,0,0,0.15)]">
                <div class="flex items-start gap-3 px-4 pt-4 pb-3">
                    {{-- Pin icon --}}
                    <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-full bg-[#E8F5E9]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5" fill="#0B3D2E" stroke="none"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-heading text-[15px] font-bold leading-tight text-bark" x-text="selectedPin.title"></h3>
                        <p class="mt-0.5 text-[12px] text-muted">
                            <span x-show="selectedPin.distance_to_start_km"><span x-text="selectedPin.distance_to_start_km.toFixed(1)"></span> km {{ __('general.away') }}</span>
                        </p>
                    </div>
                </div>
                {{-- Tags row --}}
                <div class="flex flex-wrap gap-[6px] px-4 pb-3">
                    <span class="rounded-full px-2.5 py-[3px] text-[11px] font-semibold"
                          :class="selectedPin.difficulty === 'easy' ? 'bg-amber-100 text-amber-700' : (selectedPin.difficulty === 'hard' ? 'bg-red-50 text-coral' : 'bg-[#D4EDE4] text-forest-600')"
                          x-text="selectedPin.difficulty"></span>
                    <span class="rounded-full bg-cream px-2.5 py-[3px] text-[11px] font-semibold text-muted" x-text="selectedPin.checkpoint_count + ' stops'"></span>
                    <span x-show="selectedPin.distance_to_farthest_km" class="rounded-full bg-cream px-2.5 py-[3px] text-[11px] font-semibold text-muted" x-text="selectedPin.distance_to_farthest_km.toFixed(1) + ' km'"></span>
                </div>
                {{-- View Quest button --}}
                <div class="px-4 pb-4">
                    <a :href="'/quests/' + selectedPin.id" class="block rounded-[12px] bg-forest-600 py-[13px] text-center text-[14px] font-bold text-white" wire:navigate>{{ __('general.view_quest') }} &rarr;</a>
                </div>
            </div>
        </template>
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
