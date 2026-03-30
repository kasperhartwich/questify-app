<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SessionParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User Sessions
 */
class UserSessionController extends Controller
{
    /**
     * List user's session history
     *
     * Get a paginated list of sessions the user participated in, with session and quest details.
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {"data": [{"participant_id": 1, "display_name": "Lars", "total_score": 500, "quest_completed_at": null, "session": {"id": 1, "session_code": "ABC123", "status": "completed", "quest": {"id": 1, "title": "City Walk"}}}]}
     */
    public function index(Request $request): JsonResponse
    {
        $participations = SessionParticipant::query()
            ->where('user_id', $request->user()->id)
            ->with(['questSession.quest.category', 'questSession.quest.creator'])
            ->latest()
            ->cursorPaginate(15);

        $data = $participations->through(fn ($p) => [
            'participant_id' => $p->id,
            'display_name' => $p->display_name,
            'total_score' => $p->score,
            'quest_completed_at' => $p->finished_at,
            'session' => [
                'id' => $p->questSession->id,
                'session_code' => $p->questSession->join_code,
                'status' => $p->questSession->status,
                'play_mode' => $p->questSession->play_mode,
                'quest' => [
                    'id' => $p->questSession->quest->id,
                    'title' => $p->questSession->quest->title,
                ],
                'started_at' => $p->questSession->started_at,
                'completed_at' => $p->questSession->completed_at,
            ],
        ]);

        return response()->json($data);
    }
}
