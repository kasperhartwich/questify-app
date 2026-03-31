<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;

class QuestApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    /**
     * @param  array{category_id?: int, difficulty?: string, search?: string, cursor?: string}  $filters
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/quests', array_filter($filters));
    }

    public function show(int $id): array
    {
        return $this->client->get("/quests/{$id}");
    }

    /**
     * Create a quest with nested checkpoints/questions/answers.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $coverImagePath  Local path to cover image file
     */
    public function store(array $data, ?string $coverImagePath = null): array
    {
        if ($coverImagePath) {
            return $this->client->postMultipart('/quests', $data, [
                'cover_image' => [
                    'path' => $coverImagePath,
                    'name' => basename($coverImagePath),
                ],
            ]);
        }

        return $this->client->post('/quests', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, ?string $coverImagePath = null): array
    {
        if ($coverImagePath) {
            return $this->client->postMultipart("/quests/{$id}", array_merge($data, ['_method' => 'PUT']), [
                'cover_image' => [
                    'path' => $coverImagePath,
                    'name' => basename($coverImagePath),
                ],
            ]);
        }

        return $this->client->put("/quests/{$id}", $data);
    }

    public function destroy(int $id): array
    {
        return $this->client->delete("/quests/{$id}");
    }

    public function publish(int $id): array
    {
        return $this->client->post("/quests/{$id}/publish");
    }

    public function rate(int $id, int $rating, ?string $comment = null): array
    {
        return $this->client->post("/quests/{$id}/rate", array_filter([
            'rating' => $rating,
            'comment' => $comment,
        ]));
    }

    public function flag(int $id, string $reason): array
    {
        return $this->client->post("/quests/{$id}/flag", ['reason' => $reason]);
    }
}
