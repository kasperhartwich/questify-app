<?php

namespace App\Services\Api\Resources;

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;

class SessionApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function create(int $questId, string $playMode): array
    {
        $result = $this->client->post('/sessions', [
            'quest_id' => $questId,
            'play_mode' => $playMode,
        ]);

        ApiCache::forgetPrefix('user:sessions');

        return $result;
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
        $result = $this->client->post("/sessions/{$code}/start");

        ApiCache::forgetPrefix('user:sessions');

        return $result;
    }

    public function end(string $code): array
    {
        $result = $this->client->post("/sessions/{$code}/end");

        ApiCache::forgetPrefix('user:sessions');

        return $result;
    }

    public function dashboard(string $code): array
    {
        return $this->client->get("/sessions/{$code}/dashboard");
    }
}
