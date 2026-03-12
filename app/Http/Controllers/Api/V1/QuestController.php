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

/**
 * @group Quests
 */
class QuestController extends Controller
{
    use AuthorizesRequests;

    /**
     * List quests
     *
     * Get a paginated list of published and visible quests.
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "description": "Explore the city", "difficulty": "easy", "category": {"id": 1, "name": "History"}, "creator": {"id": 1, "name": "John"}, "ratings_avg_rating": "4.5", "ratings_count": 10, "checkpoints_count": 5}]}
     */
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

    /**
     * Create quest
     *
     * Create a new quest in draft status.
     *
     * @response 201 {"quest": {"id": 1, "title": "City Walk", "status": "draft", "category": {"id": 1, "name": "History"}, "creator": {"id": 1, "name": "John"}}}
     * @response 422 scenario="Validation error" {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
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

    /**
     * Show quest
     *
     * Get detailed information about a quest including checkpoints, questions, and answers.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "City Walk", "description": "Explore the city", "status": "published", "category": {"id": 1, "name": "History"}, "checkpoints": [], "ratings_avg_rating": "4.5", "ratings_count": 10}}
     */
    public function show(Quest $quest): QuestDetailResource
    {
        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return new QuestDetailResource($quest);
    }

    /**
     * Update quest
     *
     * Update a quest's details. Only the quest creator can update.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "Updated Title", "status": "draft"}}
     * @response 403 scenario="Not authorized" {"message": "This action is unauthorized."}
     */
    public function update(UpdateQuestRequest $request, Quest $quest): QuestDetailResource
    {
        $this->authorize('update', $quest);

        $quest->update($request->validated());
        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return new QuestDetailResource($quest);
    }

    /**
     * Delete quest
     *
     * Delete a quest. Only the quest creator can delete.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"message": "Quest deleted."}
     * @response 403 scenario="Not authorized" {"message": "This action is unauthorized."}
     */
    public function destroy(Request $request, Quest $quest): JsonResponse
    {
        $this->authorize('delete', $quest);

        $quest->delete();

        return response()->json(['message' => __('quests.deleted')]);
    }

    /**
     * Publish quest
     *
     * Publish a draft quest to make it publicly available.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "City Walk", "status": "published", "published_at": "2026-03-12T00:00:00.000000Z"}}
     * @response 403 scenario="Not authorized" {"message": "This action is unauthorized."}
     */
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

    /**
     * Rate quest
     *
     * Rate a quest (1-5 stars) with an optional review. Users cannot rate their own quests.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 201 {"rating": {"id": 1, "rating": 5, "review": "Great quest!", "user": {"id": 2, "name": "Jane"}}}
     * @response 403 scenario="Own quest" {"message": "This action is unauthorized."}
     */
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

    /**
     * Flag quest
     *
     * Report a quest for moderation. Users cannot flag their own quests.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 201 {"message": "Quest flagged for review."}
     * @response 403 scenario="Own quest" {"message": "This action is unauthorized."}
     */
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
