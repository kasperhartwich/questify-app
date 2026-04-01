<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NearbyQuestResource extends QuestResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $checkpoints = $this->whenLoaded('checkpoints', fn () => $this->checkpoints->sortBy('sort_order'));
        $startingCheckpoint = $checkpoints instanceof Collection ? $checkpoints->first() : null;

        return array_merge($data, [
            'starting_checkpoint' => $startingCheckpoint ? [
                'id' => $startingCheckpoint->id,
                'title' => $startingCheckpoint->title,
                'latitude' => $startingCheckpoint->latitude,
                'longitude' => $startingCheckpoint->longitude,
            ] : null,
            'checkpoint_count' => $this->whenLoaded('checkpoints', fn () => $this->checkpoints->count()),
            'distance_to_start_km' => $this->when(isset($this->resource->distance_to_start_km), fn () => round((float) $this->resource->distance_to_start_km, 2)),
            'distance_to_farthest_km' => $this->when(isset($this->resource->distance_to_farthest_km), fn () => round((float) $this->resource->distance_to_farthest_km, 2)),
            'total_route_distance_km' => $this->when(isset($this->resource->total_route_distance_km), fn () => round((float) $this->resource->total_route_distance_km, 2)),
        ]);
    }
}
