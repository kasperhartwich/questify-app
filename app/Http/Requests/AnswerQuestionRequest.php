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
            'participant_id' => ['required', 'integer', 'exists:session_participants,id'],
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer_id' => ['nullable', 'integer', 'exists:answers,id', 'required_without:answer_text'],
            'answer_text' => ['nullable', 'string', 'required_without:answer_id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'answer_id.required_without' => __('sessions.validation.answer_required'),
            'answer_text.required_without' => __('sessions.validation.answer_required'),
        ];
    }
}
