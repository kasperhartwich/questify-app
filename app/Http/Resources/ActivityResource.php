<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $key = $this->activityType->key;

        return [
            'id' => $this->id,
            'type' => $key,
            'title' => $this->buildTitle($key),
            'subtitle' => $this->buildSubtitle($key),
            'icon' => $this->activityType->icon,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }

    private function buildTitle(string $key): string
    {
        $title = $this->metadata['quest_title'] ?? '';

        return match ($key) {
            'quest_completed' => "Completed {$title} quest",
            'quest_published' => 'Published new quest',
            'quest_created' => 'Created new quest',
            'quest_shared' => 'Shared a quest',
            'quest_rated' => 'Rated a quest',
            'quest_favourited' => 'Favourited a quest',
            default => $this->activityType->name,
        };
    }

    private function buildSubtitle(string $key): string
    {
        $title = $this->metadata['quest_title'] ?? '';

        return match ($key) {
            'quest_completed' => implode(' · ', array_filter([
                isset($this->metadata['placement']) ? $this->formatPlacement($this->metadata['placement']) : null,
                isset($this->metadata['score']) ? number_format($this->metadata['score']).' pts' : null,
            ])),
            'quest_rated' => $title.' · '.($this->metadata['rating'] ?? 0).'★',
            default => $title,
        };
    }

    private function formatPlacement(int $placement): string
    {
        return match ($placement % 10) {
            1 => $placement.'st',
            2 => $placement.'nd',
            3 => $placement.'rd',
            default => $placement.'th',
        };
    }
}
