<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
                'cover_image_url' => $this->quest->cover_image_path ? Storage::url($this->quest->cover_image_path) : null,
            ]),
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
