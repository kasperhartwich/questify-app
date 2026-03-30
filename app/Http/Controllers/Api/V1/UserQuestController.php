<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group User Quests
 */
class UserQuestController extends Controller
{
    /**
     * List user's created quests
     *
     * Get a cursor-paginated list of quests created by the authenticated user.
     *
     * @response 200 {"data": [{"id": 1, "title": "City Walk", "status": "draft", "category": {"id": 1, "name": "History"}, "sessions_count": 3}]}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $quests = $request->user()->quests()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'sessions'])
            ->latest()
            ->cursorPaginate(15);

        return QuestResource::collection($quests);
    }
}
