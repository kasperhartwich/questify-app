<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group User Quests
 */
class UserQuestController extends Controller
{
    /**
     * Created quests
     *
     * Get a paginated list of quests created by the authenticated user.
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "status": "draft", "category": {"id": 1, "name": "History"}}]}
     */
    public function created(Request $request): AnonymousResourceCollection
    {
        $quests = $request->user()->quests()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'checkpoints'])
            ->latest()
            ->paginate(15);

        return QuestResource::collection($quests);
    }

    /**
     * Played quests
     *
     * Get a paginated list of quests the authenticated user has participated in.
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "category": {"id": 1, "name": "History"}}]}
     */
    public function played(Request $request): AnonymousResourceCollection
    {
        $quests = Quest::query()
            ->whereHas('sessions.participants', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'checkpoints'])
            ->latest()
            ->paginate(15);

        return QuestResource::collection($quests);
    }
}
