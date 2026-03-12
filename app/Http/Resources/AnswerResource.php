<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'body' => $this->body,
            'sort_order' => $this->sort_order,
        ];

        if ($request->routeIs('quests.checkpoints.*') || $request->routeIs('quests.checkpoints.questions.*')) {
            $data['is_correct'] = $this->is_correct;
        }

        return $data;
    }
}
