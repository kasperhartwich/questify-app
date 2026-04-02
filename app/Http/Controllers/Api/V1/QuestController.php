<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Enums\ModerationStatus;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\FlagQuestRequest;
use App\Http\Requests\NearbyQuestRequest;
use App\Http\Requests\RateQuestRequest;
use App\Http\Requests\StoreQuestRequest;
use App\Http\Requests\UpdateQuestRequest;
use App\Http\Resources\NearbyQuestResource;
use App\Http\Resources\QuestDetailResource;
use App\Http\Resources\QuestRatingResource;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Quests
 */
class QuestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ActivityLogService $activityLogService) {}

    /**
     * List quests
     *
     * Get a cursor-paginated list of published and visible quests.
     *
     * @queryParam category_id integer Filter by category. Example: 1
     * @queryParam difficulty string Filter by difficulty (easy, medium, hard). Example: easy
     * @queryParam search string Search by title. Example: City
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "description": "Explore the city", "difficulty": "easy", "category": {"id": 1, "name": "History"}, "sessions_count": 5}]}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $quests = Quest::query()
            ->published()
            ->visible()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'sessions'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->input('difficulty')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->latest()
            ->cursorPaginate(15);

        return QuestResource::collection($quests);
    }

    /**
     * Nearby quests
     *
     * Get published quests sorted by distance from the given coordinates.
     * Returns the starting checkpoint, distance to it, distance to the farthest checkpoint,
     * and total route distance for each quest.
     *
     * @queryParam latitude number required User's latitude. Example: 55.6761
     * @queryParam longitude number required User's longitude. Example: 12.5683
     * @queryParam radius number Radius in km (default 50, max 100). Example: 25
     * @queryParam category_id integer Filter by category. Example: 1
     * @queryParam difficulty string Filter by difficulty (easy, medium, hard). Example: easy
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "distance_to_start_km": 1.23, "distance_to_farthest_km": 3.45, "total_route_distance_km": 5.67, "starting_checkpoint": {"id": 1, "title": "Start", "latitude": 55.6761, "longitude": 12.5683}}]}
     */
    public function nearby(NearbyQuestRequest $request): AnonymousResourceCollection
    {
        $latitude = (float) $request->validated('latitude');
        $longitude = (float) $request->validated('longitude');
        $radiusKm = (float) ($request->validated('radius') ?? 50);

        $quests = Quest::query()
            ->published()
            ->visible()
            ->with(['category', 'creator', 'checkpoints'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'sessions'])
            ->whereHas('checkpoints')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->input('difficulty')))
            ->get();

        $quests = $quests
            ->map(function (Quest $quest) use ($latitude, $longitude) {
                $checkpoints = $quest->checkpoints->sortBy('sort_order')->values();
                $startCheckpoint = $checkpoints->first();

                if (! $startCheckpoint) {
                    return null;
                }

                $quest->distance_to_start_km = Quest::haversineDistance(
                    $latitude, $longitude,
                    (float) $startCheckpoint->latitude, (float) $startCheckpoint->longitude,
                );

                $quest->distance_to_farthest_km = $checkpoints->max(
                    fn ($cp) => Quest::haversineDistance($latitude, $longitude, (float) $cp->latitude, (float) $cp->longitude)
                );

                $quest->total_route_distance_km = $quest->total_distance_km ?? 0;

                return $quest;
            })
            ->filter()
            ->filter(fn (Quest $quest) => $quest->distance_to_start_km <= $radiusKm)
            ->sortBy('distance_to_start_km')
            ->values();

        return NearbyQuestResource::collection($quests);
    }

    /**
     * Create quest
     *
     * Create a new quest with checkpoints, questions, and answers in a single transaction.
     *
     * @response 201 {"data": {"id": 1, "title": "City Walk", "status": "draft"}}
     * @response 422 scenario="Validation error" {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
    public function store(StoreQuestRequest $request): JsonResponse
    {
        $quest = DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Handle cover image upload
            $questData = collect($data)->except(['checkpoints', 'cover_image'])->toArray();
            if ($request->hasFile('cover_image')) {
                $questData['cover_image_path'] = $request->file('cover_image')->store('quests', 'public');
            }
            $questData['status'] = QuestStatus::Draft;

            $quest = $request->user()->quests()->create($questData);

            // Create nested checkpoints, questions, and answers
            foreach ($data['checkpoints'] as $index => $checkpointData) {
                $checkpoint = $quest->checkpoints()->create([
                    'title' => $checkpointData['title'],
                    'description' => $checkpointData['description'] ?? null,
                    'latitude' => $checkpointData['latitude'],
                    'longitude' => $checkpointData['longitude'],
                    'hint' => $checkpointData['hint'] ?? null,
                    'sort_order' => $index,
                ]);

                foreach ($checkpointData['questions'] as $questionIndex => $questionData) {
                    $question = $checkpoint->questions()->create([
                        'body' => $questionData['question_text'],
                        'type' => $questionData['question_type'],
                        'sort_order' => $questionIndex,
                    ]);

                    foreach ($questionData['answers'] as $answerIndex => $answerData) {
                        $question->answers()->create([
                            'body' => $answerData['answer_text'],
                            'is_correct' => $answerData['is_correct'],
                            'sort_order' => $answerIndex,
                        ]);
                    }
                }
            }

            return $quest;
        });

        $quest->refresh()
            ->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        $this->activityLogService->log($request->user(), ActivityType::QuestCreated, $quest, [
            'quest_title' => $quest->title,
        ]);

        return response()->json([
            'data' => new QuestDetailResource($quest),
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
     * Archive quest
     *
     * Archive a quest (soft delete). Only the quest creator can archive.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "City Walk", "status": "archived"}, "message": "Quest archived successfully."}
     * @response 403 scenario="Not authorized" {"message": "This action is unauthorized."}
     */
    public function destroy(Request $request, Quest $quest): JsonResponse
    {
        $this->authorize('delete', $quest);

        $quest->update(['status' => QuestStatus::Archived]);

        $quest->load(['category', 'creator'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        return response()->json([
            'data' => new QuestResource($quest),
            'message' => 'Quest archived successfully.',
        ]);
    }

    /**
     * Publish quest
     *
     * Submit a quest for review (public) or publish directly (private/school).
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "City Walk", "status": "pending_review"}, "message": "Quest submitted for review."}
     * @response 403 scenario="Not authorized" {"message": "This action is unauthorized."}
     */
    public function publish(Request $request, Quest $quest): JsonResponse
    {
        $this->authorize('publish', $quest);

        if ($quest->visibility === QuestVisibility::Public) {
            $quest->update(['status' => QuestStatus::PendingReview]);
            $message = 'Quest submitted for review.';
        } else {
            $quest->update(['status' => QuestStatus::Published]);
            $message = 'Quest published successfully.';
        }

        $quest->load(['category', 'creator', 'checkpoints.questions.answers'])
            ->loadAvg('ratings', 'rating')
            ->loadCount('ratings');

        $this->activityLogService->log($request->user(), ActivityType::QuestPublished, $quest, [
            'quest_title' => $quest->title,
        ]);

        return response()->json([
            'data' => new QuestDetailResource($quest),
            'message' => $message,
        ]);
    }

    /**
     * Rate quest
     *
     * Rate a quest (1-5 stars) with an optional comment. Users cannot rate their own quests.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 201 {"data": {"id": 1, "rating": 5, "comment": "Great quest!", "user": {"id": 2, "name": "Jane"}}}
     * @response 403 scenario="Own quest" {"message": "This action is unauthorized."}
     */
    public function rate(RateQuestRequest $request, Quest $quest): JsonResponse
    {
        $this->authorize('rate', $quest);

        $rating = $quest->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        $this->activityLogService->log($request->user(), ActivityType::QuestRated, $rating, [
            'quest_title' => $quest->title,
            'quest_id' => $quest->id,
            'rating' => $rating->rating,
        ]);

        return response()->json([
            'data' => new QuestRatingResource($rating->load('user')),
        ], 201);
    }

    /**
     * Flag quest
     *
     * Report a quest for moderation. Public endpoint — no authentication required.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 201 {"message": "Quest flagged for review."}
     */
    public function flag(FlagQuestRequest $request, Quest $quest): JsonResponse
    {
        $quest->moderationFlags()->create([
            'reason' => $request->validated('reason'),
            'reporter_id' => $request->user()?->id,
            'status' => ModerationStatus::Pending,
        ]);

        return response()->json([
            'message' => __('quests.flagged'),
        ], 201);
    }
}
