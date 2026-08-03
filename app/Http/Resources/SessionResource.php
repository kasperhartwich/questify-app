<?php

namespace App\Http\Resources;

use App\Enums\SessionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quest' => $this->whenLoaded('quest', fn () => [
                'id' => $this->quest->id,
                'title' => $this->quest->title,
                'cover_image_url' => $this->quest->resolveImageUrl($this->quest->cover_image_path),
                'checkpoint_count' => $this->quest->checkpoints_count,
                'checkpoint_arrival_radius_meters' => $this->quest->checkpoint_arrival_radius_meters,
            ]),
            // Business rule 7: checkpoint coordinates are gameplay data, exposed only once the
            // session is active (being played) — never on the public quest detail or the pre-join
            // preview. This is the session-scoped source the active-quest screen navigates from.
            'checkpoints' => $this->when(
                $this->status === SessionStatus::Active && $this->relationLoaded('quest') && $this->quest->relationLoaded('checkpoints'),
                fn () => $this->quest->checkpoints->sortBy('sort_order')->values()->map(fn ($cp) => [
                    'id' => $cp->id,
                    'title' => $cp->title,
                    'description' => $cp->description,
                    'latitude' => $cp->latitude,
                    'longitude' => $cp->longitude,
                    'arrival_radius_override' => $cp->arrival_radius_override,
                ])
            ),
            'host' => $this->whenLoaded('host', fn () => [
                'id' => $this->host->id,
                'name' => $this->host->name,
            ]),
            'status' => $this->status,
            'session_code' => $this->join_code,
            'play_mode' => $this->play_mode,
            'participants_count' => $this->whenCounted('participants'),
            'participants' => SessionParticipantResource::collection($this->whenLoaded('participants')),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
