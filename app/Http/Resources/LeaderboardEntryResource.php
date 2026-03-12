<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'participant_id' => $this->id,
            'display_name' => $this->display_name,
            'score' => $this->score,
            'finished_at' => $this->finished_at,
        ];
    }
}
