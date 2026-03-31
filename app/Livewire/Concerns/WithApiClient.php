<?php

namespace App\Livewire\Concerns;

use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Collection;

trait WithApiClient
{
    public function getApiProperty(): QuestifyApiClient
    {
        return app(QuestifyApiClient::class);
    }

    /**
     * Convert an array (or array of arrays) into objects so Blade templates
     * can use `->property` syntax on API response data.
     */
    protected function toObject(array $data): object
    {
        return json_decode(json_encode($data));
    }

    /**
     * Convert an array of items into a collection of objects.
     *
     * @return Collection<int, object>
     */
    protected function toObjectCollection(array $items): Collection
    {
        return collect($items)->map(fn (array $item) => $this->toObject($item));
    }
}
