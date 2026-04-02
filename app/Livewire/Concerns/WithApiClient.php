<?php

namespace App\Livewire\Concerns;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
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
        $result = json_decode(json_encode($data));

        return is_object($result) ? $result : (object) $data;
    }

    /**
     * Convert an array of items into a collection of objects.
     *
     * @return Collection<int, object>
     */
    protected function toObjectCollection(array $items): Collection
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item) => $this->toObject($item))
            ->values();
    }

    /**
     * Toggle favourite on a quest from any page that renders quest cards.
     *
     * @return array{is_favourited: bool}|null
     */
    public function toggleCardFavourite(int $questId): ?array
    {
        try {
            $response = $this->api->quests()->toggleFavourite($questId);

            return $response['data'] ?? null;
        } catch (ApiAuthenticationException) {
            session()->flush();
            $this->redirect(route('login'));

            return null;
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());

            return null;
        }
    }
}
