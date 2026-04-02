<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Favourites
 */
class QuestFavouriteController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    /**
     * List favourite quests
     *
     * Get the authenticated user's favourite quests, cursor-paginated.
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "is_favourited": true}]}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $quests = $request->user()
            ->favouriteQuests()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'sessions'])
            ->latest('quest_favourites.created_at')
            ->cursorPaginate(15);

        return QuestResource::collection($quests);
    }

    /**
     * Toggle favourite
     *
     * Add or remove a quest from the authenticated user's favourites.
     *
     * @urlParam quest integer required The quest ID. Example: 1
     *
     * @response 200 {"data": {"is_favourited": true}, "message": "Quest added to favourites."}
     * @response 200 {"data": {"is_favourited": false}, "message": "Quest removed from favourites."}
     */
    public function toggle(Request $request, Quest $quest): JsonResponse
    {
        $result = $request->user()->favouriteQuests()->toggle($quest->id);

        $isFavourited = ! empty($result['attached']);

        if ($isFavourited) {
            $this->activityLogService->log($request->user(), ActivityType::QuestFavourited, $quest, [
                'quest_title' => $quest->title,
            ]);
        }

        return response()->json([
            'data' => ['is_favourited' => $isFavourited],
            'message' => $isFavourited
                ? __('quests.favourited')
                : __('quests.unfavourited'),
        ]);
    }
}
