<style>
    .mapbox-quest-marker {
        background-color: #0B3D2E;
        border: 3px solid white;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
<div class="flex flex-col bg-cream">
    {{-- Search & Filter --}}
    <div class="flex items-center gap-2 px-[16px] pb-[10px] pt-[6px]">
        <div class="flex flex-1 items-center gap-2 rounded-[13px] border-[1.5px] border-cream-border bg-white px-[14px] py-[11px]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('general.search_quests_near_you') }}"
                class="w-full border-none bg-transparent p-0 text-[13px] text-bark placeholder-[#B0A898] focus:outline-none focus:ring-0"
            />
        </div>
        <button
            x-data="{ open: false }"
            @click="$dispatch('toggle-filters')"
            class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-[13px] bg-forest-600"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
        </button>
    </div>

    {{-- Filter dropdowns --}}
    <div x-data="{ showFilters: false }" @toggle-filters.window="showFilters = !showFilters">
        <div x-show="showFilters" x-transition class="flex gap-2 px-[16px] pb-2">
            <select wire:model.live="category" class="flex-1 rounded-[13px] border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value="">{{ __('general.all_categories') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="difficulty" class="flex-1 rounded-[13px] border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value="">{{ __('general.all_difficulties') }}</option>
                @foreach ($difficulties as $diff)
                    <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Mini Map Widget --}}
    <div class="relative mx-[16px] mb-[12px] h-[120px] overflow-hidden rounded-[14px]"
        x-data
        x-init="
            mapboxgl.accessToken = @js(config('services.mapbox.token'));
            const miniMap = new mapboxgl.Map({
                container: $refs.miniMap,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [12.5683, 55.6761],
                zoom: 12,
                attributionControl: false,
                interactive: false,
            });
            miniMap.on('load', () => {
                const quests = @js(
                    collect($quests)->filter(fn($q) => !empty($q->starting_checkpoint->latitude ?? ($q->checkpoints[0]->latitude ?? null)))
                    ->map(fn($q) => [
                        'lat' => (float) ($q->starting_checkpoint->latitude ?? $q->checkpoints[0]->latitude),
                        'lng' => (float) ($q->starting_checkpoint->longitude ?? $q->checkpoints[0]->longitude),
                    ])->values()->all()
                );
                quests.forEach(q => {
                    const el = document.createElement('div');
                    el.className = 'mapbox-quest-marker';
                    el.style.cssText = 'width:16px;height:16px;border-width:2px;';
                    new mapboxgl.Marker({ element: el }).setLngLat([q.lng, q.lat]).addTo(miniMap);
                });
                if (quests.length) {
                    const bounds = new mapboxgl.LngLatBounds();
                    quests.forEach(q => bounds.extend([q.lng, q.lat]));
                    miniMap.fitBounds(bounds, { padding: 20 });
                }
            });
        "
    >
        <div x-ref="miniMap" wire:ignore style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
        {{-- Clickable overlay to navigate to full map --}}
        <a href="/discover/map" class="absolute inset-0 z-10" wire:navigate></a>
        {{-- Nearby badge --}}
        <div class="absolute bottom-2.5 right-2.5 z-20 rounded-[10px] bg-white px-2.5 py-1 text-[10px] font-semibold text-forest-600 shadow-md">
            {{ count($quests) }} {{ __('general.quests_nearby') }}
        </div>
    </div>

    {{-- Section Header --}}
    <div class="flex items-center justify-between px-[16px] pb-2 pt-3">
        <h2 class="font-heading text-[16px] font-bold text-bark">{{ __('general.nearby_quests') }}</h2>
        <a href="/discover/map" class="text-[13px] font-semibold text-forest-400" wire:navigate>{{ __('general.see_all') }} &rarr;</a>
    </div>

    {{-- Quest Cards --}}
    <div class="space-y-[12px] px-[16px] pb-5">
        @forelse ($quests as $quest)
            <x-quest-card :quest="$quest" variant="discover" :cta-label="__('general.start_quest')" />
        @empty
            <div class="py-12 text-center text-muted">
                <p>{{ __('general.no_quests_found') }}</p>
            </div>
        @endforelse

        @if (!empty($nextCursor))
            <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-2 w-full rounded-[12px] bg-forest-600 px-4 py-[11px] text-sm font-bold text-white">
                {{ __('general.load_more') }}
            </button>
        @endif
    </div>
</div>
