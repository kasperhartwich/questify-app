<div class="flex flex-col">
    {{-- Tabs --}}
    <div class="flex border-b-2 border-cream-border px-4 dark:border-gray-700">
        <a href="/my-quests" class="flex-1 border-b-2 border-forest-600 py-3 text-center text-sm font-semibold text-forest-600 -mb-[2px] dark:border-forest-400 dark:text-forest-400">
            {{ __('general.played_quests') }}
        </a>
        <a href="/my-quests/created" class="flex-1 border-b-2 border-transparent py-3 text-center text-sm font-semibold text-muted hover:text-bark -mb-[2px] dark:text-gray-400 dark:hover:text-gray-300" wire:navigate>
            {{ __('general.created_quests') }}
        </a>
    </div>

    {{-- Session History --}}
    <div class="space-y-3 p-4">
        @forelse ($participations as $participation)
            @php $session = $participation->questSession; @endphp
            @if ($session?->quest)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:key="participation-{{ $participation->id }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <a href="/quests/{{ $session->quest->id }}" class="font-semibold text-gray-900 hover:text-forest-600 dark:text-white dark:hover:text-forest-400" wire:navigate>
                                {{ $session->quest->title }}
                            </a>
                            @if ($session->quest->category)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session->quest->category->name }}</p>
                            @endif
                        </div>
                        <span class="ml-2 inline-flex items-center rounded-full bg-forest-100 px-2.5 py-0.5 text-xs font-medium text-forest-700 dark:bg-forest-900/30 dark:text-forest-400">
                            {{ $participation->score ?? 0 }} {{ __('general.score') }}
                        </span>
                    </div>

                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ ucfirst($session->status->value) }}</span>
                        <span>{{ $session->created_at->diffForHumans() }}</span>
                        @if ($participation->finished_at)
                            <span>{{ $participation->finished_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            @endif
        @empty
            <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                <p>{{ __('general.no_sessions_found') }}</p>
            </div>
        @endforelse

        {{-- Pagination handled by API cursor --}}
    </div>
</div>
