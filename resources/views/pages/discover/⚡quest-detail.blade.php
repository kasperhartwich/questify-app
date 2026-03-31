<?php

use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Quest Detail')]
class extends Component
{
    use HandlesApiErrors, WithApiClient;

    public int $questId = 0;

    public object $questData;

    public function mount(int $quest): void
    {
        $this->questId = $quest;
        $response = $this->tryApiCall(fn () => $this->api->quests()->show($quest));
        $this->questData = $this->toObject($response['data'] ?? []);
    }

    public function startQuest(): void
    {
        $this->redirect('/quests/' . $this->questId . '/start');
    }
};
?>

<div class="flex flex-col">
    {{-- Hero Image --}}
    @if ($questData->cover_image_url)
        <img src="{{ $questData->cover_image_url }}" alt="{{ $questData->title }}" class="h-56 w-full object-cover" />
    @else
        <div class="relative flex h-56 items-center justify-center overflow-hidden bg-forest-600">
            <div class="pointer-events-none absolute right-[-30px] top-[-30px] h-[150px] w-[150px] rounded-full border-[24px] border-amber-400/10"></div>
            <span class="relative z-10 px-6 text-center font-heading text-2xl font-extrabold text-white">{{ $questData->title }}</span>
        </div>
    @endif

    <div class="space-y-6 p-4">
        {{-- Title & Category --}}
        <div>
            <h1 class="font-heading text-2xl font-extrabold text-bark dark:text-white">{{ $questData->title }}</h1>
            @if ($questData->category)
                <span class="mt-1 inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($questData->category->icon)
                        <span>{{ $questData->category->icon }}</span>
                    @endif
                    {{ $questData->category->name }}
                </span>
            @endif
            @if ($questData->user)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('quests.by_creator', ['name' => $questData->user->name]) }}</p>
            @endif
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-4 gap-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ count($questData->checkpoints ?? []) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.checkpoints') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ !empty($questData->difficulty) ? ucfirst($questData->difficulty) : '-' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.difficulty') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ !empty($questData->average_rating) ? number_format($questData->average_rating, 1) : '-' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.rating') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $questData->sessions_count ?? 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.sessions') }}</p>
            </div>
        </div>

        {{-- Description --}}
        @if ($questData->description)
            <div>
                <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('quests.description') }}</h2>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $questData->description }}</p>
            </div>
        @endif

        {{-- Map Preview --}}
        @php
            $startCheckpoint = collect($questData->checkpoints ?? [])->first();
        @endphp
        @if ($startCheckpoint?->latitude && $startCheckpoint?->longitude)
            <div>
                <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('general.quest_map') }}</h2>
                <div
                    id="detail-map"
                    class="h-48 w-full rounded-xl bg-gray-200 dark:bg-gray-700"
                    x-data="{
                        init() {
                            if (typeof google !== 'undefined') {
                                const map = new google.maps.Map(this.$el, {
                                    center: { lat: {{ $startCheckpoint->latitude }}, lng: {{ $startCheckpoint->longitude }} },
                                    zoom: 14,
                                    mapTypeControl: false,
                                    streetViewControl: false,
                                    zoomControl: false,
                                });
                                new google.maps.Marker({
                                    position: { lat: {{ $startCheckpoint->latitude }}, lng: {{ $startCheckpoint->longitude }} },
                                    map: map,
                                    title: '{{ addslashes($questData->title) }}',
                                });
                            }
                        }
                    }"
                >
                    <div class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
                        <p>{{ __('general.quest_map') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Ratings --}}
        @if (($questData->ratings_count ?? 0) > 0)
            <div>
                <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('general.ratings') }} ({{ ($questData->ratings_count ?? 0) }})</h2>
                <div class="flex items-center gap-2">
                    <span class="text-3xl font-bold text-yellow-500">★ {{ number_format(($questData->average_rating ?? 0), 1) }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">/ 5</span>
                </div>
            </div>
        @endif

        {{-- CTAs --}}
        <div class="flex gap-3 pb-4">
            <button wire:click="startQuest" class="flex-1 rounded-xl bg-amber-400 px-4 py-3.5 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                {{ __('general.start_quest') }}
            </button>
            <a href="/" class="flex-1 rounded-xl border-[1.5px] border-cream-border px-4 py-3.5 text-center text-sm font-semibold text-bark hover:bg-cream-dark dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                {{ __('general.join') }}
            </a>
        </div>
    </div>
</div>
