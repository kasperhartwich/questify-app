<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;

class SessionApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function create(int $questId, string $playMode): array
    {
        return $this->client->post('/sessions', [
            'quest_id' => $questId,
            'play_mode' => $playMode,
        ]);
    }

    public function show(string $code): array
    {
        return $this->client->get("/sessions/{$code}");
    }

    public function join(string $code, string $displayName, ?int $userId = null): array
    {
        return $this->client->post("/sessions/{$code}/join", array_filter([
            'display_name' => $displayName,
            'user_id' => $userId,
        ]));
    }

    public function start(string $code): array
    {
        return $this->client->post("/sessions/{$code}/start");
    }

    public function end(string $code): array
    {
        return $this->client->post("/sessions/{$code}/end");
    }

    public function dashboard(string $code): array
    {
        return $this->client->get("/sessions/{$code}/dashboard");
    }
}
