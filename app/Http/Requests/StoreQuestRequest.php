<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'difficulty' => ['required', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'visibility' => ['required', 'string', Rule::in(['public', 'private', 'school'])],
            'estimated_duration_minutes' => ['required', 'integer', 'min:1'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'access_code' => ['nullable', 'string', 'max:20'],
            'checkpoint_arrival_radius_meters' => ['nullable', 'integer', 'min:10', 'max:500'],
            'wrong_answer_behaviour' => ['nullable', 'string', Rule::in(['retry_free', 'retry_penalty', 'lockout', 'three_strikes_hint'])],
            'wrong_answer_penalty_points' => ['nullable', 'integer', 'min:0'],
            'wrong_answer_lockout_seconds' => ['nullable', 'integer', 'min:0'],
            'scoring_points_per_correct' => ['nullable', 'integer', 'min:0'],
            'scoring_speed_bonus_enabled' => ['nullable', 'boolean'],
            'scoring_wrong_attempt_penalty_enabled' => ['nullable', 'boolean'],
            'scoring_quest_completion_time_bonus_enabled' => ['nullable', 'boolean'],
            'checkpoints' => ['required', 'array', 'min:1'],
            'checkpoints.*.title' => ['required', 'string', 'max:255'],
            'checkpoints.*.description' => ['nullable', 'string'],
            'checkpoints.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'checkpoints.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'checkpoints.*.hint' => ['nullable', 'string'],
            'checkpoints.*.questions' => ['required', 'array', 'min:1'],
            'checkpoints.*.questions.*.question_text' => ['required', 'string'],
            'checkpoints.*.questions.*.question_type' => ['required', 'string', Rule::in(['multiple_choice', 'true_false', 'open_text'])],
            'checkpoints.*.questions.*.answers' => ['required', 'array', 'min:1'],
            'checkpoints.*.questions.*.answers.*.answer_text' => ['required', 'string', 'max:255'],
            'checkpoints.*.questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => __('quests.validation.category_required'),
            'category_id.exists' => __('quests.validation.category_invalid'),
            'title.required' => __('quests.validation.title_required'),
            'title.max' => __('quests.validation.title_max'),
            'difficulty.required' => __('quests.validation.difficulty_required'),
        ];
    }
}
