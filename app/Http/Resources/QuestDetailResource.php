<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class QuestDetailResource extends QuestResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $firstCheckpoint = $this->whenLoaded('checkpoints', function () {
            $checkpoint = $this->checkpoints->sortBy('sort_order')->first();

            if (! $checkpoint) {
                return null;
            }

            return [
                'id' => $checkpoint->id,
                'title' => $checkpoint->title,
                'latitude' => $checkpoint->latitude,
                'longitude' => $checkpoint->longitude,
            ];
        });

        return array_merge($data, [
            'visibility' => $this->visibility,
            'status' => $this->status,
            'starting_checkpoint' => $firstCheckpoint,
            'checkpoint_count' => $this->whenCounted('checkpoints'),
            'checkpoint_arrival_radius_meters' => $this->checkpoint_arrival_radius_meters,
            'checkpoints' => $this->whenLoaded('checkpoints', fn () => $this->checkpoints->sortBy('sort_order')->map(fn ($cp) => [
                'id' => $cp->id,
                'title' => $cp->title,
                'description' => $cp->description,
                'latitude' => $cp->latitude,
                'longitude' => $cp->longitude,
                'arrival_radius_override' => $cp->arrival_radius_override,
            ])->values()),
            'scoring_points_per_correct' => $this->scoring_points_per_correct,
            'scoring_speed_bonus_enabled' => $this->scoring_speed_bonus_enabled,
            'scoring_wrong_attempt_penalty_enabled' => $this->scoring_wrong_attempt_penalty_enabled,
            'scoring_quest_completion_time_bonus_enabled' => $this->scoring_quest_completion_time_bonus_enabled,
            'wrong_answer_behaviour' => $this->wrong_answer_behaviour,
            'wrong_answer_penalty_points' => $this->wrong_answer_penalty_points,
            'wrong_answer_lockout_seconds' => $this->wrong_answer_lockout_seconds,
        ]);
    }
}
