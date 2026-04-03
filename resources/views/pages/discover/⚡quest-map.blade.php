<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Facades\Geolocation;
use Native\Mobile\Facades\System;

new
#[Title('Quest Map')]
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

<div class="relative flex h-screen flex-col bg-[#E4EDE4]"
    x-data="{
        map: null,
        markers: [],
        circles: [],
        pins: @js($pins),
        selectedPin: null,
        filterDifficulty: 'all',
        isSatellite: false,
        userLocated: false,
        init() {
            const boot = () => {
                if (typeof mapboxgl === 'undefined') {
                    setTimeout(boot, 50);
                    return;
                }
                try {
                    mapboxgl.accessToken = @js(config('services.mapbox.token'));
                    this.map = new mapboxgl.Map({
                        container: this.$refs.mapCanvas,
                        style: 'mapbox://styles/mapbox/streets-v12',
                        center: [12.5683, 55.6761],
                        zoom: 12,
                        attributionControl: false,
                    });
                    this.map.addControl(new mapboxgl.AttributionControl({ compact: true }), 'bottom-left');
                    this.map.on('error', (e) => console.warn('Mapbox error:', e));
                    this.map.on('load', () => {
                        this.addMarkers();
                        this.locateUser();
                    });
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
                this.map.flyTo({ center: [lng, lat], zoom: 13 });
                if (!this.userLocated) {
                    this.userLocated = true;
                }
                this.pins = $wire.pins;
                this.addMarkers();
            });
        },
        addMarkers() {
            this.markers.forEach(m => m.remove());
            this.markers = [];
            this.removeCircles();
            this.pins.forEach((pin, index) => {
                const el = document.createElement('div');
                el.className = 'mapbox-quest-marker';
                const span = document.createElement('span');
                span.className = 'mapbox-quest-marker-num';
                span.textContent = pin.checkpoint_count || '';
                el.appendChild(span);
                const marker = new mapboxgl.Marker({ element: el })
                    .setLngLat([pin.longitude, pin.latitude])
                    .addTo(this.map);
                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectedPin = pin;
                    this.map.flyTo({ center: [pin.longitude, pin.latitude], zoom: 14 });
                    this.drawCircle(pin);
                });
                this.markers.push(marker);
            });
        },
        removeCircles() {
            this.circles.forEach(id => {
                if (this.map.getLayer(id)) this.map.removeLayer(id);
                if (this.map.getLayer(id + '-line')) this.map.removeLayer(id + '-line');
                if (this.map.getSource(id)) this.map.removeSource(id);
            });
            this.circles = [];
        },
        drawCircle(pin) {
            this.removeCircles();
            if (!pin.distance_to_farthest_km || pin.distance_to_farthest_km <= 0) return;

            const id = 'quest-radius-' + pin.id;
            const center = [pin.longitude, pin.latitude];
            const radiusKm = pin.distance_to_farthest_km;
            const points = 64;
            const coords = [];

            for (let i = 0; i <= points; i++) {
                const angle = (i / points) * 2 * Math.PI;
                const dx = radiusKm * Math.cos(angle);
                const dy = radiusKm * Math.sin(angle);
                const lat = center[1] + (dy / 111.32);
                const lng = center[0] + (dx / (111.32 * Math.cos(center[1] * Math.PI / 180)));
                coords.push([lng, lat]);
            }

            this.map.addSource(id, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    geometry: { type: 'Polygon', coordinates: [coords] },
                },
            });
            this.map.addLayer({
                id: id,
                type: 'fill',
                source: id,
                paint: {
                    'fill-color': '#0B3D2E',
                    'fill-opacity': 0.08,
                },
            });
            this.map.addLayer({
                id: id + '-line',
                type: 'line',
                source: id,
                paint: {
                    'line-color': '#0B3D2E',
                    'line-width': 2,
                    'line-opacity': 0.4,
                    'line-dasharray': [3, 2],
                },
            });
            this.circles.push(id);
        },
        toggleStyle() {
            this.isSatellite = !this.isSatellite;
            const style = this.isSatellite ? 'satellite-streets-v12' : 'streets-v12';
            this.map.setStyle('mapbox://styles/mapbox/' + style);
            this.map.once('style.load', () => {
                this.addMarkers();
                if (this.selectedPin) this.drawCircle(this.selectedPin);
            });
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
                    this.map.flyTo({ center: [lng, lat], zoom: 13 });
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
        .mapbox-quest-marker {
            width: 32px;
            height: 32px;
            background-color: #0B3D2E;
            border: 3px solid white;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mapbox-quest-marker:hover {
            background-color: #15573F;
        }
        .mapbox-quest-marker-num {
            transform: rotate(45deg);
            font-family: 'Exo 2', sans-serif;
            font-size: 11px;
            font-weight: 800;
            color: white;
        }
    </style>

    {{-- Full-screen map --}}
    <div x-ref="mapCanvas" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>

    {{-- Floating search bar --}}
    <div class="absolute left-0 right-0 top-[56px] z-10 px-4">
        <div class="flex gap-2">
            <a href="/discover/list" class="flex h-[44px] w-[36px] shrink-0 items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]" wire:navigate>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2C1810" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="flex flex-1 items-center gap-2 rounded-[12px] bg-white px-[14px] py-[12px] shadow-[0_2px_10px_rgba(0,0,0,0.15)]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <span class="text-[14px] text-[#B0A898]">{{ __('general.search_area') }}...</span>
            </div>
        </div>
        {{-- Filter chips --}}
        <div class="mt-2 flex gap-[6px] overflow-x-auto pb-[2px]">
            <button
                @click="filterDifficulty = 'all'"
                class="whitespace-nowrap rounded-full border-[1.5px] px-3 py-[5px] text-[12px] font-semibold transition-colors"
                :class="filterDifficulty === 'all' ? 'border-forest-600 bg-forest-600 text-white' : 'border-cream-border bg-white text-muted'"
            >{{ __('general.all') }}</button>
            <button
                @click="filterDifficulty = 'easy'"
                class="whitespace-nowrap rounded-full border-[1.5px] px-3 py-[5px] text-[12px] font-semibold transition-colors"
                :class="filterDifficulty === 'easy' ? 'border-forest-600 bg-forest-600 text-white' : 'border-cream-border bg-white text-muted'"
            >{{ __('general.easy') }}</button>
            <button
                @click="filterDifficulty = 'medium'"
                class="whitespace-nowrap rounded-full border-[1.5px] px-3 py-[5px] text-[12px] font-semibold transition-colors"
                :class="filterDifficulty === 'medium' ? 'border-forest-600 bg-forest-600 text-white' : 'border-cream-border bg-white text-muted'"
            >{{ __('general.medium') }}</button>
            <button
                @click="filterDifficulty = 'hard'"
                class="whitespace-nowrap rounded-full border-[1.5px] px-3 py-[5px] text-[12px] font-semibold transition-colors"
                :class="filterDifficulty === 'hard' ? 'border-forest-600 bg-forest-600 text-white' : 'border-cream-border bg-white text-muted'"
            >{{ __('general.hard') }}</button>
            <button
                class="whitespace-nowrap rounded-full border-[1.5px] border-cream-border bg-white px-3 py-[5px] text-[12px] font-semibold text-muted"
            >&lt; 30 min</button>
        </div>
    </div>

    {{-- Map style toggle --}}
    <button
        @click="toggleStyle()"
        class="absolute bottom-[280px] right-4 z-10 flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]"
    >
        <template x-if="!isSatellite">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        </template>
        <template x-if="isSatellite">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3,7 9,5 15,7 21,5 21,19 15,21 9,19 3,21"/><line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="7" x2="15" y2="21"/></svg>
        </template>
    </button>

    {{-- My location button --}}
    <button
        @click="locateUser()"
        class="absolute bottom-[230px] right-4 z-10 flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
    </button>

    {{-- Bottom sheet --}}
    <div class="absolute inset-x-0 bottom-0 z-10 rounded-t-[22px] bg-white px-4 pb-5 pt-3 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
        <div class="mx-auto mb-[14px] h-1 w-9 rounded-full bg-cream-border"></div>
        <p class="mb-3 font-heading text-[15px] font-bold text-bark">
            <span x-text="pins.length">{{ count($pins) }}</span> {{ __('general.quests_in_area') }}
        </p>

        {{-- Selected quest card --}}
        <template x-if="selectedPin">
            <a :href="'/quests/' + selectedPin.id" class="block overflow-hidden rounded-[16px] bg-white shadow-sm" wire:navigate>
                <div class="relative overflow-hidden bg-forest-600 px-4 pb-3 pt-[14px]">
                    <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                    <div>
                        <h3 class="font-heading text-[15px] font-bold text-white" x-text="selectedPin.title"></h3>
                        <p class="mt-1 text-[11px] text-white/70">
                            <span x-text="selectedPin.checkpoint_count"></span> checkpoints
                            <span x-show="selectedPin.distance_to_start_km"> &middot; <span x-text="selectedPin.distance_to_start_km.toFixed(1)"></span> km away</span>
                        </p>
                    </div>
                </div>
                <div class="px-4 py-[10px]">
                    <span class="block rounded-[10px] bg-forest-600 py-[10px] text-center text-[13px] font-bold text-white">{{ __('general.view_quest') }} &rarr;</span>
                </div>
            </a>
        </template>
        <template x-if="!selectedPin">
            <p class="py-4 text-center text-[13px] text-muted">{{ __('general.tap_pin_to_preview') }}</p>
        </template>
    </div>
</div>
