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
            'cover_image_url' => $this->resolveImageUrl($this->cover_image_path),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon,
                'color' => $this->category->color,
            ]),
            'difficulty' => $this->difficulty,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'average_rating' => $this->ratings_avg_rating ? round((float) $this->ratings_avg_rating, 1) : null,
            'sessions_count' => $this->whenCounted('sessions'),
            'user' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
