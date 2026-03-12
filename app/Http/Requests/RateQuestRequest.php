<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => __('quests.validation.rating_required'),
            'rating.min' => __('quests.validation.rating_min'),
            'rating.max' => __('quests.validation.rating_max'),
        ];
    }
}
