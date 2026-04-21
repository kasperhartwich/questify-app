<?php

namespace App\Services\Api\Resources;

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;

class QuestApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    /**
     * @param  array{category_id?: int, difficulty?: string, search?: string, cursor?: string}  $filters
     */
    public function list(array $filters = []): array
    {
        $filtered = array_filter($filters);
        $hash = md5(serialize($filtered));

        return ApiCache::remember("quests:list:{$hash}", fn () => $this->client->get('/quests', $filtered));
    }

    /**
     * @param  array{radius?: float, category_id?: int, difficulty?: string}  $filters
     */
    public function nearby(float $latitude, float $longitude, array $filters = []): array
    {
        $params = array_filter([
            'latitude' => $latitude,
            'longitude' => $longitude,
            ...$filters,
        ]);
        $hash = md5(serialize($params));

        return ApiCache::remember("quests:nearby:{$hash}", fn () => $this->client->get('/quests/nearby', $params));
    }

    public function show(int $id): array
    {
        return ApiCache::remember("quests:show:{$id}", fn () => $this->client->get("/quests/{$id}"));
    }

    /**
     * Create a quest with nested checkpoints/questions/answers.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $coverImagePath  Local path to cover image file
     */
    public function store(array $data, ?string $coverImagePath = null): array
    {
        $result = $coverImagePath
            ? $this->client->postMultipart('/quests', $data, [
                'cover_image' => [
                    'path' => $coverImagePath,
                    'name' => basename($coverImagePath),
                ],
            ])
            : $this->client->post('/quests', $data);

        ApiCache::forgetPrefix('quests:');
        ApiCache::forgetPrefix('user:quests:');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, ?string $coverImagePath = null): array
    {
        $result = $coverImagePath
            ? $this->client->postMultipart("/quests/{$id}", array_merge($data, ['_method' => 'PUT']), [
                'cover_image' => [
                    'path' => $coverImagePath,
                    'name' => basename($coverImagePath),
                ],
            ])
            : $this->client->put("/quests/{$id}", $data);

        ApiCache::forgetPrefix('quests:');
        ApiCache::forgetPrefix('user:quests:');

        return $result;
    }

    public function destroy(int $id): array
    {
        $result = $this->client->delete("/quests/{$id}");

        ApiCache::forgetPrefix('quests:');
        ApiCache::forgetPrefix('user:quests:');

        return $result;
    }

    public function publish(int $id): array
    {
        $result = $this->client->post("/quests/{$id}/publish");

        ApiCache::forgetPrefix('quests:');
        ApiCache::forgetPrefix('user:quests:');

        return $result;
    }

    public function rate(int $id, int $rating, ?string $comment = null): array
    {
        $result = $this->client->post("/quests/{$id}/rate", array_filter([
            'rating' => $rating,
            'comment' => $comment,
        ]));

        ApiCache::forget("quests:show:{$id}");

        return $result;
    }

    public function flag(int $id, string $reason): array
    {
        return $this->client->post("/quests/{$id}/flag", ['reason' => $reason]);
    }

    public function toggleFavourite(int $id): array
    {
        $result = $this->client->post("/quests/{$id}/favourite");

        ApiCache::forget("quests:show:{$id}");
        ApiCache::forgetPrefix('quests:list:');
        ApiCache::forgetPrefix('user:favourites:');

        return $result;
    }
}
