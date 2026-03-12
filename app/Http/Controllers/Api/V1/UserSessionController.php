<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User Sessions
 */
class UserSessionController extends Controller
{
    /**
     * List sessions
     *
     * Get a paginated list of sessions where the user is host or participant.
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "join_code": "ABC123", "status": "completed", "quest": {"id": 1, "title": "City Walk"}}]}
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = QuestSession::query()
            ->where(function ($query) use ($request) {
                $query->where('host_id', $request->user()->id)
                    ->orWhereHas('participants', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id);
                    });
            })
            ->with(['quest.category', 'quest.creator'])
            ->latest()
            ->paginate(15);

        return response()->json($sessions);
    }
}
