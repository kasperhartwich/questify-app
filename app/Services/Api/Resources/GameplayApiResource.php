<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;

class GameplayApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function arrived(string $code, int $participantId, int $checkpointId, float $latitude, float $longitude): array
    {
        return $this->client->post("/sessions/{$code}/arrived", [
            'participant_id' => $participantId,
            'checkpoint_id' => $checkpointId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function answer(string $code, int $participantId, int $questionId, ?int $answerId = null, ?string $answerText = null): array
    {
        return $this->client->post("/sessions/{$code}/answer", array_filter([
            'participant_id' => $participantId,
            'question_id' => $questionId,
            'answer_id' => $answerId,
            'answer_text' => $answerText,
        ]));
    }

    public function leaderboard(string $code): array
    {
        return $this->client->get("/sessions/{$code}/leaderboard");
    }
}
