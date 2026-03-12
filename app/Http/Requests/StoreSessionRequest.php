<?php

namespace App\Http\Requests;

use App\Enums\PlayMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'quest_id' => ['required', 'integer', 'exists:quests,id'],
            'play_mode' => ['required', Rule::enum(PlayMode::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'quest_id.required' => __('sessions.validation.quest_required'),
            'quest_id.exists' => __('sessions.validation.quest_not_found'),
            'play_mode.required' => __('sessions.validation.play_mode_required'),
        ];
    }
}
