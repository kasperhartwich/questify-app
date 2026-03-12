<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\QuestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\FlagQuestRequest;
use App\Http\Requests\RateQuestRequest;
use App\Http\Requests\StoreQuestRequest;
use App\Http\Requests\UpdateQuestRequest;
use App\Http\Resources\QuestDetailResource;
use App\Http\Resources\QuestRatingResource;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuestController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $quests = Quest::query()
            ->published()
            ->visible()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'checkpoints'])
            ->latest()
            ->paginate(15);

        return QuestResource::collection($quests);
    }

    public function store(StoreQuestRequest $request): JsonResponse
    {
        $quest = $request->user()->quests()->create(
            array_merge($request->validated(), ['status' => QuestStatus::Draft])
        );

        $quest->load(['category', 'creator']);

        return response()->json([
            'quest' => new QuestDetailResource($quest),
        ], 201);
    }

    public function show(Quest $quest): QuestDetailResource
    {
        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return new QuestDetailResource($quest);
    }

    public function update(UpdateQuestRequest $request, Quest $quest): QuestDetailResource
    {
        $this->authorize('update', $quest);

        $quest->update($request->validated());
        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return new QuestDetailResource($quest);
    }

    public function destroy(Request $request, Quest $quest): JsonResponse
    {
        $this->authorize('delete', $quest);

        $quest->delete();

        return response()->json(['message' => __('quests.deleted')]);
    }

    public function publish(Request $request, Quest $quest): QuestDetailResource
    {
        $this->authorize('publish', $quest);

        $quest->update([
            'status' => QuestStatus::Published,
            'published_at' => now(),
        ]);

        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return new QuestDetailResource($quest);
    }

    public function rate(RateQuestRequest $request, Quest $quest): JsonResponse
    {
        $this->authorize('rate', $quest);

        $rating = $quest->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return response()->json([
            'rating' => new QuestRatingResource($rating->load('user')),
        ], 201);
    }

    public function flag(FlagQuestRequest $request, Quest $quest): JsonResponse
    {
        $this->authorize('flag', $quest);

        $quest->moderationFlags()->create(
            array_merge($request->validated(), [
                'reporter_id' => $request->user()->id,
                'status' => ModerationStatus::Pending,
            ])
        );

        return response()->json([
            'message' => __('quests.flagged'),
        ], 201);
    }
}
