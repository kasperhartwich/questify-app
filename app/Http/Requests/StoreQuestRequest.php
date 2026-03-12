<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
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
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'visibility' => ['required', Rule::enum(QuestVisibility::class)],
            'play_mode' => ['required', Rule::enum(PlayMode::class)],
            'wrong_answer_behaviour' => ['required', Rule::enum(WrongAnswerBehaviour::class)],
            'time_limit_per_question' => ['nullable', 'integer', 'min:5', 'max:300'],
            'shuffle_questions' => ['boolean'],
            'shuffle_answers' => ['boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100'],
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
