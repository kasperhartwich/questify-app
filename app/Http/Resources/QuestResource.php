<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_path' => $this->cover_image_path,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'play_mode' => $this->play_mode,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'average_rating' => $this->whenAggregated('ratings', 'rating', 'avg'),
            'ratings_count' => $this->whenAggregated('ratings', 'rating', 'count'),
            'checkpoints_count' => $this->whenCounted('checkpoints'),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
