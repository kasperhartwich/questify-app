<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionParticipantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'display_name' => $this->display_name,
            'score' => $this->score,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }
}
