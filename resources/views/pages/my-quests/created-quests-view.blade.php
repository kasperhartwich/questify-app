<div class="flex flex-col">
    {{-- Tabs --}}
    <div class="flex border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <a href="/my-quests" class="flex-1 border-b-2 border-transparent px-4 py-3 text-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300" wire:navigate>
            {{ __('general.played_quests') }}
        </a>
        <a href="/my-quests/created" class="flex-1 border-b-2 border-indigo-600 px-4 py-3 text-center text-sm font-semibold text-indigo-600 dark:border-indigo-400 dark:text-indigo-400">
            {{ __('general.created_quests') }}
        </a>
    </div>

    {{-- Created Quests --}}
    <div class="space-y-3 p-4">
        @forelse ($quests as $quest)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:key="created-{{ $quest->id }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <a href="/quests/{{ $quest->id }}" class="font-semibold text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400" wire:navigate>
                            {{ $quest->title }}
                        </a>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ match($quest->status) {
                                    \App\Enums\QuestStatus::Draft => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    \App\Enums\QuestStatus::Published => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    \App\Enums\QuestStatus::Archived => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                } }}">
                                {{ ucfirst($quest->status->value) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ $quest->sessions_count }} {{ __('general.sessions') }}</span>
                    @if ($quest->ratings_avg_rating)
                        <span>★ {{ number_format($quest->ratings_avg_rating, 1) }} ({{ $quest->ratings_count }})</span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="mt-3 flex gap-2">
                    <a href="/quests/{{ $quest->id }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400" wire:navigate>
                        {{ __('general.view_details') }}
                    </a>
                    <a href="/quests/{{ $quest->id }}/edit" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        {{ __('general.edit') }}
                    </a>
                    @if ($quest->status !== \App\Enums\QuestStatus::Archived)
                        <button wire:click="archiveQuest({{ $quest->id }})" wire:confirm="{{ __('general.confirm') }}?" class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            {{ __('general.archive') }}
                        </button>
                    @endif
                    @if ($quest->status === \App\Enums\QuestStatus::Published)
                        <a href="/quests/{{ $quest->id }}/start" class="rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-600 dark:bg-green-900/30 dark:text-green-400">
                            {{ __('general.start_quest') }}
                        </a>
                    @endif
                </div>
            </div>
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
