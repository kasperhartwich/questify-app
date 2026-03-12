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

class CheckpointController extends Controller
{
    use AuthorizesRequests;

    public function index(Quest $quest): AnonymousResourceCollection
    {
        $this->authorize('update', $quest);

        return CheckpointResource::collection(
            $quest->checkpoints()->with('questions.answers')->get()
        );
    }

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

    public function show(Quest $quest, Checkpoint $checkpoint): CheckpointResource
    {
        $this->authorize('update', $quest);

        $checkpoint->load('questions.answers');

        return new CheckpointResource($checkpoint);
    }

    public function update(UpdateCheckpointRequest $request, Quest $quest, Checkpoint $checkpoint): CheckpointResource
    {
        $this->authorize('update', $quest);

        $checkpoint->update($request->validated());
        $checkpoint->load('questions.answers');

        return new CheckpointResource($checkpoint);
    }

    public function destroy(Quest $quest, Checkpoint $checkpoint): JsonResponse
    {
        $this->authorize('update', $quest);

        $checkpoint->delete();

        return response()->json(['message' => __('general.deleted')]);
    }

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
