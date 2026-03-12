<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quest' => new QuestResource($this->whenLoaded('quest')),
            'host' => new UserResource($this->whenLoaded('host')),
            'status' => $this->status,
            'join_code' => $this->join_code,
            'play_mode' => $this->play_mode,
            'participant_count' => $this->whenCounted('participants'),
            'participants' => SessionParticipantResource::collection($this->whenLoaded('participants')),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
