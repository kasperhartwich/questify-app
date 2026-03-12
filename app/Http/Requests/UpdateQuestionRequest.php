<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
            'type' => ['sometimes', Rule::enum(QuestionType::class)],
            'body' => ['sometimes', 'string', 'max:2000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'hint' => ['nullable', 'string', 'max:500'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'answers' => ['array'],
            'answers.*.id' => ['nullable', 'integer', 'exists:answers,id'],
            'answers.*.body' => ['required_with:answers', 'string', 'max:500'],
            'answers.*.is_correct' => ['required_with:answers', 'boolean'],
            'answers.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
