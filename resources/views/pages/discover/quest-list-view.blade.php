<div class="flex flex-col">
    {{-- Search & Filters --}}
    <div class="space-y-3 px-4 pb-3 pt-2">
        <div class="flex gap-2">
            <div class="flex flex-1 items-center gap-2 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2.5 shadow-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8A8078" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('general.search') }}..."
                    class="w-full border-none bg-transparent p-0 text-sm text-bark placeholder-muted focus:outline-none focus:ring-0 dark:text-white"
                />
            </div>
            <button class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-forest-600">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
            </button>
        </div>

        <div class="flex gap-2">
            <select wire:model.live="category" class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2 text-sm text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('general.all_categories') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="difficulty" class="flex-1 rounded-xl border-[1.5px] border-cream-border bg-white px-3 py-2 text-sm text-bark dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('general.all_difficulties') }}</option>
                @foreach ($difficulties as $diff)
                    <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Map Toggle --}}
        <a href="/discover/map" class="block rounded-xl bg-forest-50 px-4 py-2 text-center text-sm font-semibold text-forest-600 dark:bg-forest-900/30 dark:text-forest-400" wire:navigate>
            {{ __('general.quest_map') }}
        </a>
    </div>

    {{-- Section Header --}}
    <div class="flex items-center justify-between px-4 pb-2 pt-1">
        <h2 class="font-heading text-sm font-bold text-bark dark:text-white">{{ __('general.nearby_quests') ?? 'Nearby Quests' }}</h2>
    </div>

    {{-- Quest Cards --}}
    <div class="space-y-3 px-4 pb-4">
        @forelse ($quests as $quest)
            <a href="/quests/{{ $quest->id }}" class="block overflow-hidden rounded-[14px] bg-white shadow-sm dark:bg-gray-800" wire:navigate wire:key="quest-{{ $quest->id }}">
                {{-- Cover Image / Header --}}
                @if (!empty($quest->cover_image_url))
                    <img src="{{ $quest->cover_image_url }}" alt="{{ $quest->title }}" class="h-28 w-full object-cover" />
                @else
                    <div class="relative overflow-hidden bg-forest-600 px-3.5 py-3">
                        <div class="pointer-events-none absolute right-[-20px] top-[-20px] h-[80px] w-[80px] rounded-full border-[14px] border-white/[0.08]"></div>
                        <h3 class="font-heading text-sm font-bold leading-tight text-white">{{ $quest->title }}</h3>
                        @if ($quest->user?->name ?? null)
                            <p class="mt-1 text-[10px] text-white/50">{{ __('quests.by_creator', ['name' => $quest->user->name]) }}</p>
                        @endif
                    </div>
                @endif

                <div class="px-3.5 py-3">
                    @if (!empty($quest->cover_image_url))
                        <h3 class="font-heading text-sm font-bold text-bark dark:text-white">{{ $quest->title }}</h3>
                    @endif

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px] text-muted">
                        @if (!empty($quest->estimated_duration_minutes))
                            <span class="flex items-center gap-1">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ $quest->estimated_duration_minutes }} min
                            </span>
                        @endif
                        @if (!empty($quest->checkpoints_count))
                            <span class="flex items-center gap-1">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                                {{ $quest->checkpoints_count }} {{ __('general.stops') ?? 'stops' }}
                            </span>
                        @endif
                        @if (!empty($quest->difficulty))
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold
                                {{ match($quest->difficulty) {
                                    'easy' => 'bg-forest-50 text-forest-600',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    'hard' => 'bg-red-50 text-coral',
                                    default => 'bg-cream-dark text-muted',
                                } }}">
                                {{ ucfirst($quest->difficulty) }}
                            </span>
                        @endif
                        @if (!empty($quest->average_rating))
                            <span class="ml-auto inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700">
                                ★ {{ number_format($quest->average_rating, 1) }}
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="py-12 text-center text-muted dark:text-gray-400">
                <p>{{ __('general.no_quests_found') }}</p>
            </div>
        @endforelse

        @if (!empty($nextCursor))
            <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-2 w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-forest-700">
                {{ __('general.load_more') }}
            </button>
        @endif
    </div>
</div>
