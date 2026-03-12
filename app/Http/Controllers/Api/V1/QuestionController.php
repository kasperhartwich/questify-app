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

class QuestionController extends Controller
{
    use AuthorizesRequests;

    public function index(Quest $quest, Checkpoint $checkpoint): AnonymousResourceCollection
    {
        $this->authorize('update', $quest);

        return QuestionResource::collection(
            $checkpoint->questions()->with('answers')->get()
        );
    }

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

    public function show(Quest $quest, Checkpoint $checkpoint, Question $question): QuestionResource
    {
        $this->authorize('update', $quest);

        $question->load('answers');

        return new QuestionResource($question);
    }

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

    public function destroy(Quest $quest, Checkpoint $checkpoint, Question $question): JsonResponse
    {
        $this->authorize('update', $quest);

        $question->delete();

        return response()->json(['message' => __('general.deleted')]);
    }
}
