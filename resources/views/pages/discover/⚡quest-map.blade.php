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
                    'title' => $quest->title,
                    'latitude' => (float) $startCheckpoint->latitude,
                    'longitude' => (float) $startCheckpoint->longitude,
                ];
            })
            ->all();
    }
};
?>

<div class="relative flex h-screen flex-col bg-[#E4EDE4]"
    x-data="{
        map: null,
        pins: @js($pins),
        selectedPin: null,
        filterDifficulty: 'all',
        init() {
            if (typeof google !== 'undefined') {
                this.initMap();
            }
        },
        initMap() {
            this.map = new google.maps.Map(document.getElementById('quest-map-canvas'), {
                center: { lat: 55.6761, lng: 12.5683 },
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: false,
            });
            this.pins.forEach(pin => {
                const marker = new google.maps.Marker({
                    position: { lat: pin.latitude, lng: pin.longitude },
                    map: this.map,
                    title: pin.title,
                });
                marker.addListener('click', () => {
                    this.selectedPin = pin;
                });
            });
        },
        centerOnUser() {
            if (navigator.geolocation && this.map) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    this.map.setCenter({ lat: pos.coords.latitude, lng: pos.coords.longitude });
                });
            }
        }
    }"
>
    {{-- Full-screen map --}}
    <div id="quest-map-canvas" class="absolute inset-0">
        <div class="flex h-full items-center justify-center text-sm text-muted">{{ __('general.quest_map') }}</div>
    </div>

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

    {{-- My location button --}}
    <button
        @click="centerOnUser()"
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

        {{-- Selected quest card or first quest --}}
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
