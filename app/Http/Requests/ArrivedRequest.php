<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArrivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'integer', 'exists:session_participants,id'],
            'checkpoint_id' => ['required', 'integer', 'exists:checkpoints,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
