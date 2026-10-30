<?php

namespace App\Services\Api\Resources;

use App\Services\Api\ApiCache;
use App\Services\Api\ParticipantToken;
use App\Services\Api\QuestifyApiClient;

class SessionApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function create(int $questId, string $playMode, ?string $accessCode = null): array
    {
        $result = $this->client->post('/sessions', array_filter([
            'quest_id' => $questId,
            'play_mode' => $playMode,
            'access_code' => $accessCode,
        ], fn ($value) => $value !== null));

        ApiCache::forgetPrefix('user:sessions');

        return $result;
    }

    /**
     * The server only hands out checkpoint coordinates up to the given participant's current stop,
     * and the player list only to the session's own players: the participant token proves which.
     */
    public function show(string $code, ?int $participantId = null): array
    {
        return $this->client->get("/sessions/{$code}", array_filter([
            'participant_id' => $participantId ?: null,
        ]), ParticipantToken::headers($code));
    }

    public function join(string $code, string $displayName, ?int $userId = null): array
    {
        $result = $this->client->post("/sessions/{$code}/join", array_filter([
            'display_name' => $displayName,
            'user_id' => $userId,
        ]));

        ParticipantToken::remember($code, $result['data']['participant_token'] ?? null);
        ApiCache::forgetPrefix('user:sessions');

        return $result;
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

    public function leave(string $code): array
    {
        $result = $this->client->post("/sessions/{$code}/leave", [], ParticipantToken::headers($code));

        ApiCache::forgetPrefix('user:sessions');

        return $result;
    }

    public function dashboard(string $code): array
    {
        return $this->client->get("/sessions/{$code}/dashboard");
    }
}
