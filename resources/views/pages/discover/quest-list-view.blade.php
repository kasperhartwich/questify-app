<div class="flex flex-col bg-cream">
    {{-- Search & Filter --}}
    <div class="flex gap-2 px-3.5 pb-2.5 pt-1.5">
        <div class="flex flex-1 items-center gap-2 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2.5 shadow-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search quests near you..."
                class="w-full border-none bg-transparent p-0 text-xs text-bark placeholder-[#B0A898] focus:outline-none focus:ring-0"
            />
        </div>
        <button
            x-data="{ open: false }"
            @click="$dispatch('toggle-filters')"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px] bg-forest-600"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
        </button>
    </div>

    {{-- Filter dropdowns --}}
    <div x-data="{ showFilters: false }" @toggle-filters.window="showFilters = !showFilters">
        <div x-show="showFilters" x-transition class="flex gap-2 px-3.5 pb-2">
            <select wire:model.live="category" class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value="">{{ __('general.all_categories') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="difficulty" class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2 text-xs text-bark">
                <option value="">{{ __('general.all_difficulties') }}</option>
                @foreach ($difficulties as $diff)
                    <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Map Widget --}}
    <a href="/discover/map" class="relative mx-3.5 mb-2.5 block h-[100px] overflow-hidden rounded-xl bg-[#E8F0E8]" wire:navigate>
        <div class="absolute left-0 top-1/4 h-px w-full bg-forest-600/[0.08]"></div>
        <div class="absolute left-0 top-1/2 h-px w-full bg-forest-600/[0.08]"></div>
        <div class="absolute left-0 top-3/4 h-px w-full bg-forest-600/[0.08]"></div>
        <div class="absolute left-1/3 top-0 h-full w-px bg-forest-600/[0.08]"></div>
        <div class="absolute left-2/3 top-0 h-full w-px bg-forest-600/[0.08]"></div>
        <div class="absolute left-0 top-[45%] h-2 w-full rounded-sm bg-white"></div>
        <div class="absolute left-[35%] top-0 h-full w-2 rounded-sm bg-white"></div>
        <div class="absolute left-0 top-[70%] h-1.5 w-full rounded-sm bg-white opacity-70"></div>
        <div class="absolute left-[30%] top-[20%]">
            <div class="h-[18px] w-[18px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-amber-400 shadow-md"></div>
        </div>
        <div class="absolute left-[60%] top-[55%]">
            <div class="h-[14px] w-[14px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-forest-600 shadow-sm"></div>
        </div>
        <div class="absolute left-[70%] top-[30%]">
            <div class="h-[14px] w-[14px] origin-center rotate-[-45deg] rounded-full rounded-bl-none bg-coral shadow-sm"></div>
        </div>
        <div class="absolute left-[30%] top-[55%] h-2.5 w-2.5 rounded-full bg-[#2563EB] shadow-[0_0_0_4px_rgba(37,99,235,0.2)]"></div>
        <div class="absolute bottom-2 right-2 rounded-lg bg-white px-2 py-1 text-[9px] font-semibold text-forest-600 shadow-md">
            📍 {{ count($quests) }} {{ __('general.quests_nearby') }}
        </div>
    </a>

    {{-- Section Header --}}
    <div class="flex items-center justify-between px-4 pb-2 pt-3">
        <h2 class="font-heading text-[13px] font-bold text-bark">{{ __('general.nearby_quests') }}</h2>
        <a href="/discover/map" class="text-[11px] font-semibold text-forest-400" wire:navigate>{{ __('general.see_all') }}</a>
    </div>

    {{-- Quest Cards --}}
    <div class="space-y-2.5 px-3.5 pb-4">
        @forelse ($quests as $quest)
            <x-quest-card :quest="$quest" variant="discover" :cta-label="__('general.start_quest')" />
        @empty
            <div class="py-12 text-center text-muted">
                <p>{{ __('general.no_quests_found') }}</p>
            </div>
        @endforelse

        @if (!empty($nextCursor))
            <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-2 w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-bold text-white">
                {{ __('general.load_more') }}
            </button>
        @endif
    </div>
</div>
