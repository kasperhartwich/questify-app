<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A player as other players see it: no linked account beyond its id, and that
 * only where the caller knows it already (the host's own, the caller's own).
 */
class SessionParticipantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $knownAccount = $this->user_id !== null
            && ($this->user_id === $this->questSession?->host_id || $this->user_id === $request->user('sanctum')?->id);

        return [
            'id' => $this->id,
            'user_id' => $knownAccount ? $this->user_id : null,
            'display_name' => $this->display_name,
            'score' => $this->score,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }
}
