<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Questions
 *
 * Manage questions within a checkpoint. Requires quest ownership.
 */
class QuestionController extends Controller
{
    use AuthorizesRequests;

    /**
     * List questions
     *
     * Get all questions for a checkpoint with their answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "type": "multiple_choice", "body": "What year?", "points": 10, "answers": []}]}
     */
    public function index(Quest $quest, Checkpoint $checkpoint): AnonymousResourceCollection
    {
        $this->authorize('update', $quest);

        return QuestionResource::collection(
            $checkpoint->questions()->with('answers')->get()
        );
    }

    /**
     * Create question
     *
     * Add a new question to a checkpoint with optional inline answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     *
     * @response 201 {"question": {"id": 1, "type": "multiple_choice", "body": "What year?", "points": 10, "answers": []}}
     */
    public function store(StoreQuestionRequest $request, Quest $quest, Checkpoint $checkpoint): JsonResponse
    {
        $this->authorize('update', $quest);

        $data = $request->validated();
        $answers = $data['answers'] ?? [];
        unset($data['answers']);

        if (! isset($data['sort_order'])) {
            $data['sort_order'] = ($checkpoint->questions()->max('sort_order') ?? -1) + 1;
        }

        $question = $checkpoint->questions()->create($data);

        foreach ($answers as $index => $answerData) {
            $question->answers()->create(array_merge($answerData, [
                'sort_order' => $answerData['sort_order'] ?? $index,
            ]));
        }

        $question->load('answers');

        return response()->json([
            'question' => new QuestionResource($question),
        ], 201);
    }

    /**
     * Show question
     *
     * Get a specific question with its answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     * @urlParam question integer required The question ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "type": "multiple_choice", "body": "What year?", "points": 10, "answers": []}}
     */
    public function show(Quest $quest, Checkpoint $checkpoint, Question $question): QuestionResource
    {
        $this->authorize('update', $quest);

        $question->load('answers');

        return new QuestionResource($question);
    }

    /**
     * Update question
     *
     * Update a question and optionally manage its answers inline. Answers not included in the array will be deleted.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     * @urlParam question integer required The question ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "type": "multiple_choice", "body": "Updated?", "points": 10, "answers": []}}
     */
    public function update(UpdateQuestionRequest $request, Quest $quest, Checkpoint $checkpoint, Question $question): QuestionResource
    {
        $this->authorize('update', $quest);

        $data = $request->validated();
        $answers = $data['answers'] ?? null;
        unset($data['answers']);

        $question->update($data);

        if ($answers !== null) {
            $existingIds = [];

            foreach ($answers as $index => $answerData) {
                if (isset($answerData['id'])) {
                    $question->answers()->where('id', $answerData['id'])->update([
                        'body' => $answerData['body'],
                        'is_correct' => $answerData['is_correct'],
                        'sort_order' => $answerData['sort_order'] ?? $index,
                    ]);
                    $existingIds[] = $answerData['id'];
                } else {
                    $answer = $question->answers()->create(array_merge($answerData, [
                        'sort_order' => $answerData['sort_order'] ?? $index,
                    ]));
                    $existingIds[] = $answer->id;
                }
            }

            $question->answers()->whereNotIn('id', $existingIds)->delete();
        }

        $question->load('answers');

        return new QuestionResource($question);
    }

    /**
     * Delete question
     *
     * Remove a question from a checkpoint.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     * @urlParam question integer required The question ID. Example: 1
     *
     * @response 200 {"message": "Deleted."}
     */
    public function destroy(Quest $quest, Checkpoint $checkpoint, Question $question): JsonResponse
    {
        $this->authorize('update', $quest);

        $question->delete();

        return response()->json(['message' => __('general.deleted')]);
    }
}
