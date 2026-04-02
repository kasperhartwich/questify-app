<?php

namespace App\Http\Resources;

use App\Enums\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->buildTitle(),
            'subtitle' => $this->buildSubtitle(),
            'icon' => $this->resolveIcon(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }

    private function buildTitle(): string
    {
        $title = $this->metadata['quest_title'] ?? '';

        return match ($this->type) {
            ActivityType::QuestCompleted => "Completed {$title} quest",
            ActivityType::QuestPublished => 'Published new quest',
            ActivityType::QuestCreated => 'Created new quest',
            ActivityType::QuestShared => 'Shared a quest',
            ActivityType::QuestRated => 'Rated a quest',
            ActivityType::QuestFavourited => 'Favourited a quest',
        };
    }

    private function buildSubtitle(): string
    {
        $title = $this->metadata['quest_title'] ?? '';

        return match ($this->type) {
            ActivityType::QuestCompleted => implode(' · ', array_filter([
                isset($this->metadata['placement']) ? $this->formatPlacement($this->metadata['placement']) : null,
                isset($this->metadata['score']) ? number_format($this->metadata['score']).' pts' : null,
            ])),
            ActivityType::QuestRated => $title.' · '.($this->metadata['rating'] ?? 0).'★',
            default => $title,
        };
    }

    private function resolveIcon(): string
    {
        return match ($this->type) {
            ActivityType::QuestCompleted => 'checkmark',
            ActivityType::QuestPublished => 'map_pin',
            ActivityType::QuestCreated => 'pencil',
            ActivityType::QuestShared => 'share',
            ActivityType::QuestRated => 'star',
            ActivityType::QuestFavourited => 'heart',
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
