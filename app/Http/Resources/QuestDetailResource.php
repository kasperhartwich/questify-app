<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestDetailResource extends JsonResource
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
            'wrong_answer_behaviour' => $this->wrong_answer_behaviour,
            'time_limit_per_question' => $this->time_limit_per_question,
            'shuffle_questions' => $this->shuffle_questions,
            'shuffle_answers' => $this->shuffle_answers,
            'max_participants' => $this->max_participants,
            'join_code' => $this->join_code,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'checkpoints' => CheckpointResource::collection($this->whenLoaded('checkpoints')),
            'average_rating' => $this->whenAggregated('ratings', 'rating', 'avg'),
            'ratings_count' => $this->whenAggregated('ratings', 'rating', 'count'),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
