<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Quest Map')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    /** @var array<int, array{id: int, title: string, latitude: float, longitude: float}> */
    public array $pins = [];

    public function mount(): void
    {
        $this->loadPins();
    }

    public function loadPins(): void
    {
        $response = $this->tryApiCall(fn () => $this->api->quests()->list()) ?? ['data' => []];

        $this->pins = collect($response['data'] ?? [])
            ->filter(fn ($quest) => ! empty($quest['checkpoints'][0]['latitude']))
            ->map(function ($quest) {
                $startCheckpoint = $quest['checkpoints'][0] ?? null;

                return [
                    'id' => $quest['id'],
                    'title' => $quest['title'] ?? '',
                    'latitude' => (float) ($startCheckpoint['latitude'] ?? 0),
                    'longitude' => (float) ($startCheckpoint['longitude'] ?? 0),
                ];
            })
            ->all();
    }
};
?>

<div class="relative flex h-screen flex-col bg-[#E4EDE4]"
    x-data="{
        map: null,
        markers: [],
        pins: @js($pins),
        selectedPin: null,
        filterDifficulty: 'all',
        isSatellite: false,
        addMarkers() {
            this.markers.forEach(m => m.remove());
            this.markers = [];
            this.pins.forEach(pin => {
                const el = document.createElement('div');
                el.className = 'mapbox-quest-marker';
                const marker = new mapboxgl.Marker({ element: el })
                    .setLngLat([pin.longitude, pin.latitude])
                    .addTo(this.map);
                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectedPin = pin;
                    this.map.flyTo({ center: [pin.longitude, pin.latitude], zoom: 14 });
                });
                this.markers.push(marker);
            });
        },
        toggleStyle() {
            this.isSatellite = !this.isSatellite;
            const style = this.isSatellite ? 'satellite-streets-v12' : 'streets-v12';
            this.map.setStyle('mapbox://styles/mapbox/' + style);
            this.map.once('style.load', () => this.addMarkers());
        },
    }"
    x-init="
        mapboxgl.accessToken = @js(config('services.mapbox.token'));
        map = new mapboxgl.Map({
            container: $refs.mapCanvas,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [12.5683, 55.6761],
            zoom: 12,
            attributionControl: false,
        });
        map.addControl(new mapboxgl.AttributionControl({ compact: true }), 'bottom-left');
        map.on('load', () => addMarkers());
    "
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
        }
        .mapbox-quest-marker:hover {
            background-color: #15573F;
        }
    </style>

    {{-- Full-screen map --}}
    <div x-ref="mapCanvas" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>

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
        {{-- Satellite icon (globe) when on streets, map icon when on satellite --}}
        <template x-if="!isSatellite">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        </template>
        <template x-if="isSatellite">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3,7 9,5 15,7 21,5 21,19 15,21 9,19 3,21"/><line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="7" x2="15" y2="21"/></svg>
        </template>
    </button>

    {{-- My location button --}}
    <button
        @click="
            if (navigator.geolocation && map) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    map.flyTo({ center: [pos.coords.longitude, pos.coords.latitude], zoom: 14 });
                });
            }
        "
        class="absolute bottom-[230px] right-4 z-10 flex h-[44px] w-[44px] items-center justify-center rounded-[12px] bg-white shadow-[0_2px_10px_rgba(0,0,0,0.15)]"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B3D2E" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
    </button>

    {{-- Bottom sheet --}}
    <div class="absolute inset-x-0 bottom-0 z-10 rounded-t-[22px] bg-white px-4 pb-5 pt-3 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
        <div class="mx-auto mb-[14px] h-1 w-9 rounded-full bg-cream-border"></div>
        <p class="mb-3 font-heading text-[15px] font-bold text-bark">
            {{ count($pins) }} {{ __('general.quests_in_area') }}
        </p>

        {{-- Selected quest card --}}
        <template x-if="selectedPin">
            <a :href="'/quests/' + selectedPin.id" class="block overflow-hidden rounded-[16px] bg-white shadow-sm" wire:navigate>
                <div class="relative overflow-hidden bg-forest-600 px-4 pb-3 pt-[14px]">
                    <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-heading text-[15px] font-bold text-white" x-text="selectedPin.title"></h3>
                        </div>
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
