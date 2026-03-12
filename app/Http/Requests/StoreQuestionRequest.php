<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string', 'max:2000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'hint' => ['nullable', 'string', 'max:500'],
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'answers' => ['array'],
            'answers.*.body' => ['required_with:answers', 'string', 'max:500'],
            'answers.*.is_correct' => ['required_with:answers', 'boolean'],
            'answers.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => __('validation.required', ['attribute' => 'type']),
            'body.required' => __('validation.required', ['attribute' => 'body']),
            'points.required' => __('validation.required', ['attribute' => 'points']),
        ];
    }
}
