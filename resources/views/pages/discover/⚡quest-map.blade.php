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

<div class="flex flex-col h-full">
    {{-- Map Container --}}
    <div
        id="quest-map"
        class="h-96 w-full bg-gray-200 dark:bg-gray-700"
        x-data="{
            map: null,
            pins: @js($pins),
            init() {
                if (typeof google !== 'undefined') {
                    this.initMap();
                }
            },
            initMap() {
                this.map = new google.maps.Map(this.$el, {
                    center: { lat: 55.6761, lng: 12.5683 },
                    zoom: 12,
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                this.pins.forEach(pin => {
                    const marker = new google.maps.Marker({
                        position: { lat: pin.latitude, lng: pin.longitude },
                        map: this.map,
                        title: pin.title,
                    });
                    marker.addListener('click', () => {
                        window.location.href = '/quests/' + pin.id;
                    });
                });
            }
        }"
    >
        <div class="flex h-full items-center justify-center text-gray-500 dark:text-gray-400">
            <p>{{ __('general.quest_map') }}</p>
        </div>
    </div>

    {{-- Toggle to List View --}}
    <div class="p-4">
        <a href="/discover/list" class="block rounded-lg bg-white px-4 py-3 text-center font-medium text-indigo-600 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-indigo-400 dark:ring-gray-700" wire:navigate>
            {{ __('general.quest_list') }}
        </a>
    </div>
</div>
