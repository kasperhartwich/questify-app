<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckpointResource extends JsonResource
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
            'order_index' => $this->sort_order,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
