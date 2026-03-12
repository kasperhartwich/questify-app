<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckpointRequest;
use App\Http\Requests\UpdateCheckpointRequest;
use App\Http\Resources\CheckpointResource;
use App\Models\Checkpoint;
use App\Models\Quest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Checkpoints
 *
 * Manage checkpoints within a quest. Requires quest ownership.
 */
class CheckpointController extends Controller
{
    use AuthorizesRequests;

    /**
     * List checkpoints
     *
     * Get all checkpoints for a quest with their questions and answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "title": "Start Point", "description": "Begin here", "sort_order": 0, "questions": []}]}
     */
    public function index(Quest $quest): AnonymousResourceCollection
    {
        $this->authorize('update', $quest);

        return CheckpointResource::collection(
            $quest->checkpoints()->with('questions.answers')->get()
        );
    }

    /**
     * Create checkpoint
     *
     * Add a new checkpoint to a quest. Sort order auto-increments if not provided.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 201 {"checkpoint": {"id": 1, "title": "Start Point", "description": "Begin here", "sort_order": 0, "questions": []}}
     */
    public function store(StoreCheckpointRequest $request, Quest $quest): JsonResponse
    {
        $this->authorize('update', $quest);

        $data = $request->validated();

        if (! isset($data['sort_order'])) {
            $data['sort_order'] = ($quest->checkpoints()->max('sort_order') ?? -1) + 1;
        }

        $checkpoint = $quest->checkpoints()->create($data);
        $checkpoint->load('questions.answers');

        return response()->json([
            'checkpoint' => new CheckpointResource($checkpoint),
        ], 201);
    }

    /**
     * Show checkpoint
     *
     * Get a specific checkpoint with its questions and answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "Start Point", "description": "Begin here", "sort_order": 0, "questions": []}}
     */
    public function show(Quest $quest, Checkpoint $checkpoint): CheckpointResource
    {
        $this->authorize('update', $quest);

        $checkpoint->load('questions.answers');

        return new CheckpointResource($checkpoint);
    }

    /**
     * Update checkpoint
     *
     * Update a checkpoint's details.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "Updated Title", "sort_order": 0, "questions": []}}
     */
    public function update(UpdateCheckpointRequest $request, Quest $quest, Checkpoint $checkpoint): CheckpointResource
    {
        $this->authorize('update', $quest);

        $checkpoint->update($request->validated());
        $checkpoint->load('questions.answers');

        return new CheckpointResource($checkpoint);
    }

    /**
     * Delete checkpoint
     *
     * Remove a checkpoint from a quest.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     * @urlParam checkpoint integer required The checkpoint ID. Example: 1
     *
     * @response 200 {"message": "Deleted."}
     */
    public function destroy(Quest $quest, Checkpoint $checkpoint): JsonResponse
    {
        $this->authorize('update', $quest);

        $checkpoint->delete();

        return response()->json(['message' => __('general.deleted')]);
    }

    /**
     * Reorder checkpoints
     *
     * Set the sort order of checkpoints by providing an ordered array of checkpoint IDs.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @bodyParam order integer[] required Ordered array of checkpoint IDs. Example: [3, 1, 2]
     *
     * @response 200 {"message": "Reordered."}
     */
    public function reorder(Request $request, Quest $quest): JsonResponse
    {
        $this->authorize('update', $quest);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:checkpoints,id'],
        ]);

        foreach ($request->input('order') as $index => $checkpointId) {
            Checkpoint::where('id', $checkpointId)
                ->where('quest_id', $quest->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['message' => __('general.reordered')]);
    }
}
