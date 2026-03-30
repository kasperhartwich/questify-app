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
            'id' => $this->id,
            'display_name' => $this->display_name,
            'total_score' => $this->score,
            'current_checkpoint_index' => $this->current_checkpoint_index,
            'quest_completed_at' => $this->finished_at,
        ];
    }
}
