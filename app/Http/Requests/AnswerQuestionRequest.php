<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'answer_id' => ['required_without:open_ended_answer', 'nullable', 'integer', 'exists:answers,id'],
            'open_ended_answer' => ['required_without:answer_id', 'nullable', 'string', 'max:1000'],
            'time_taken_seconds' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'answer_id.required_without' => __('sessions.validation.answer_required'),
            'open_ended_answer.required_without' => __('sessions.validation.answer_required'),
            'time_taken_seconds.required' => __('sessions.validation.time_required'),
        ];
    }
}
