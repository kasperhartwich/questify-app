<div class="flex flex-col">
    {{-- Search & Filters --}}
    <div class="space-y-3 bg-white p-4 shadow-sm dark:bg-gray-800">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('general.search') }}..."
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />

        <div class="flex gap-2">
            <select wire:model.live="category" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('general.all_categories') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="difficulty" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('general.all_difficulties') }}</option>
                @foreach ($difficulties as $diff)
                    <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Map Toggle --}}
        <a href="/discover/map" class="block rounded-lg bg-indigo-50 px-4 py-2 text-center text-sm font-medium text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400" wire:navigate>
            {{ __('general.quest_map') }}
        </a>
    </div>

    {{-- Quest Cards --}}
    <div class="space-y-4 p-4">
        @forelse ($quests as $quest)
            <a href="/quests/{{ $quest->id }}" class="block overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:navigate wire:key="quest-{{ $quest->id }}">
                {{-- Cover Image --}}
                @if (!empty($quest->cover_image_url))
                    <img src="{{ $quest->cover_image_url }}" alt="{{ $quest->title }}" class="h-40 w-full object-cover" />
                @else
                    <div class="flex h-40 items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600">
                        <span class="text-2xl font-bold text-white">{{ Str::limit($quest->title, 20) }}</span>
                    </div>
                @endif

                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $quest->title }}</h3>

                    <div class="mt-2 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        @if (!empty($quest->difficulty))
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ match($quest->difficulty) {
                                    'easy' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'hard' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-700',
                                } }}">
                                {{ ucfirst($quest->difficulty) }}
                            </span>
                        @endif

                        @if (!empty($quest->estimated_duration_minutes))
                            <span>{{ $quest->estimated_duration_minutes }} min</span>
                        @endif

                        @if (!empty($quest->average_rating))
                            <span>★ {{ number_format($quest->average_rating, 1) }}</span>
                        @endif
                    </div>

                    @if (!empty($quest->description))
                        <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $quest->description }}</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                <p>{{ __('general.no_quests_found') }}</p>
            </div>
        @endforelse

        @if (!empty($nextCursor))
            <button wire:click="$set('cursor', '{{ $nextCursor }}')" class="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                {{ __('general.load_more') }}
            </button>
        @endif
    </div>
</div>
