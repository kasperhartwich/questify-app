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

    public array $questData = [];

    public function mount(int $quest): void
    {
        $response = $this->tryApiCall(fn () => $this->api->quests()->show($quest));
        $this->questData = $response['data'] ?? [];
    }

    public function startQuest(): void
    {
        $this->redirect('/quests/' . $this->questData['id'] . '/start');
    }
};
?>

<div class="flex flex-col">
    {{-- Hero Image --}}
    @if ($quest->cover_image_path)
        <img src="{{ Storage::url($quest->cover_image_path) }}" alt="{{ $quest->title }}" class="h-56 w-full object-cover" />
    @else
        <div class="flex h-56 items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600">
            <span class="text-3xl font-bold text-white">{{ $quest->title }}</span>
        </div>
    @endif

    <div class="space-y-6 p-4">
        {{-- Title & Category --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quest->title }}</h1>
            @if ($quest->category)
                <span class="mt-1 inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($quest->category->icon)
                        <span>{{ $quest->category->icon }}</span>
                    @endif
                    {{ $quest->category->name }}
                </span>
            @endif
            @if ($quest->creator)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('quests.by_creator', ['name' => $quest->creator->name]) }}</p>
            @endif
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-4 gap-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $quest->checkpoints_count }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.checkpoints') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $quest->difficulty ? ucfirst($quest->difficulty->value) : '-' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.difficulty') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $quest->ratings_avg_rating ? number_format($quest->ratings_avg_rating, 1) : '-' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.rating') }}</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $quest->ratings_count }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.ratings') }}</p>
            </div>
        </div>

        {{-- Description --}}
        @if ($quest->description)
            <div>
                <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('quests.description') }}</h2>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $quest->description }}</p>
            </div>
        @endif

        {{-- Map Preview --}}
        @php
            $startCheckpoint = $quest->checkpoints->first();
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
                                    title: '{{ addslashes($quest->title) }}',
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
        @if ($quest->ratings_count > 0)
            <div>
                <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('general.ratings') }} ({{ $quest->ratings_count }})</h2>
                <div class="flex items-center gap-2">
                    <span class="text-3xl font-bold text-yellow-500">★ {{ number_format($quest->ratings_avg_rating, 1) }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">/ 5</span>
                </div>
            </div>
        @endif

        {{-- CTAs --}}
        <div class="flex gap-3 pb-4">
            <button wire:click="startQuest" class="flex-1 rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                {{ __('general.start_quest') }}
            </button>
            <a href="/" class="flex-1 rounded-lg border border-indigo-600 px-4 py-3 text-center font-semibold text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-gray-800">
                {{ __('general.join') }}
            </a>
        </div>
    </div>
</div>
