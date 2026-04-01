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
    <a href="/discover/map" class="relative mx-[16px] mb-[12px] block h-[120px] overflow-hidden rounded-[14px] bg-[#E4EDE4]" wire:navigate>
        {{-- Decorative road lines --}}
        <div class="absolute left-0 top-[40%] h-[3px] w-full rounded-sm bg-white/60"></div>
        <div class="absolute left-[35%] top-0 h-full w-[3px] rounded-sm bg-white/60"></div>
        <div class="absolute left-0 top-[70%] h-[2px] w-full rounded-sm bg-white/40"></div>
        <div class="absolute left-[65%] top-0 h-full w-[2px] rounded-sm bg-white/40"></div>
        {{-- Colored pins --}}
        <div class="absolute left-[28%] top-[18%]">
            <div class="h-[18px] w-[18px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-amber-400 shadow-md"></div>
        </div>
        <div class="absolute left-[58%] top-[52%]">
            <div class="h-[14px] w-[14px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-forest-600 shadow-sm"></div>
        </div>
        <div class="absolute left-[72%] top-[28%]">
            <div class="h-[14px] w-[14px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-coral shadow-sm"></div>
        </div>
        {{-- Blue location dot --}}
        <div class="absolute left-[42%] top-[50%] h-3 w-3 rounded-full bg-[#2563EB] shadow-[0_0_0_4px_rgba(37,99,235,0.2)]"></div>
        {{-- Nearby badge --}}
        <div class="absolute bottom-2.5 right-2.5 rounded-[10px] bg-white px-2.5 py-1 text-[10px] font-semibold text-forest-600 shadow-md">
            {{ count($quests) }} {{ __('general.quests_nearby') }}
        </div>
    </a>

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
