<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SessionStatus;
use App\Events\ParticipantJoined;
use App\Events\SessionEnded;
use App\Events\SessionStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinSessionRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Resources\SessionResource;
use App\Models\Quest;
use App\Models\QuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @group Sessions
 *
 * Manage quest sessions: create, join, start, and end.
 */
class SessionController extends Controller
{
    /**
     * Create session
     *
     * Create a new quest session with a unique 6-character session code.
     *
     * @response 201 {"data": {"id": 1, "session_code": "ABC123", "status": "waiting", "play_mode": "solo", "quest": {"id": 1, "title": "City Walk"}, "host": {"id": 1, "name": "John"}, "started_at": null, "completed_at": null, "created_at": "2026-03-30T00:00:00.000000Z"}}
     */
    public function store(StoreSessionRequest $request): JsonResponse
    {
        $quest = Quest::findOrFail($request->validated('quest_id'));

        $session = QuestSession::create([
            'quest_id' => $quest->id,
            'host_id' => $request->user()->id,
            'status' => SessionStatus::Waiting,
            'join_code' => $this->generateUniqueCode(),
            'play_mode' => $request->validated('play_mode'),
        ]);

        $session->load(['quest', 'host']);

        return response()->json([
            'data' => [
                'id' => $session->id,
                'session_code' => $session->join_code,
                'status' => $session->status,
                'play_mode' => $session->play_mode,
                'quest' => [
                    'id' => $session->quest->id,
                    'title' => $session->quest->title,
                ],
                'host' => [
                    'id' => $session->host->id,
                    'name' => $session->host->name,
                ],
                'started_at' => $session->started_at,
                'completed_at' => $session->completed_at,
                'created_at' => $session->created_at,
            ],
        ], 201);
    }

    /**
     * Show session
     *
     * Get session details by session code. Publicly accessible.
     *
     * @unauthenticated
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @response 200 {"data": {"id": 1, "session_code": "ABC123", "status": "waiting", "quest": {"id": 1, "title": "City Walk"}}}
     */
    public function show(string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->with(['quest.category', 'host', 'participants.user'])
            ->withCount('participants')
            ->firstOrFail();

        return response()->json([
            'data' => new SessionResource($session),
        ]);
    }

    /**
     * Join session
     *
     * Join a waiting session as a participant. No auth required.
     *
     * @unauthenticated
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @bodyParam display_name string required The player display name. Example: Lars
     * @bodyParam user_id integer optional The user ID if authenticated. Example: null
     *
     * @response 200 {"data": {"participant_id": 7, "display_name": "Lars", "session_code": "XK92PL", "quest": {"title": "Copenhagen History Hunt", "cover_image_url": "..."}, "status": "waiting"}, "message": "Joined session successfully."}
     */
    public function join(JoinSessionRequest $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::Waiting)
            ->with('quest')
            ->firstOrFail();

        $participant = $session->participants()->create([
            'user_id' => $request->validated('user_id'),
            'display_name' => $request->validated('display_name'),
        ]);

        broadcast(new ParticipantJoined($session->join_code, $participant))->toOthers();

        return response()->json([
            'data' => [
                'participant_id' => $participant->id,
                'display_name' => $participant->display_name,
                'session_code' => $session->join_code,
                'quest' => [
                    'title' => $session->quest->title,
                    'cover_image_url' => $session->quest->cover_image_path
                        ? Storage::url($session->quest->cover_image_path)
                        : null,
                ],
                'status' => $session->status,
            ],
            'message' => 'Joined session successfully.',
        ]);
    }

    /**
     * Start session
     *
     * Start a waiting session. Only the host can start the session.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @response 200 {"data": {"id": 1, "session_code": "ABC123", "status": "active", "started_at": "2026-03-30T12:00:00.000000Z"}}
     * @response 403 scenario="Not host" {"message": "Only the host can perform this action."}
     */
    public function start(Request $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::Waiting)
            ->firstOrFail();

        if ($session->host_id !== $request->user()->id) {
            return response()->json([
                'message' => __('sessions.host_only'),
            ], 403);
        }

        $session->update([
            'status' => SessionStatus::Active,
            'started_at' => now(),
        ]);

        broadcast(new SessionStarted($session));

        return response()->json([
            'data' => [
                'id' => $session->id,
                'session_code' => $session->join_code,
                'status' => $session->status,
                'started_at' => $session->started_at,
            ],
        ]);
    }

    /**
     * End session
     *
     * End an active session. Only the host can end the session.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @response 200 {"data": {"id": 1, "session_code": "ABC123", "status": "completed", "completed_at": "2026-03-30T14:00:00.000000Z"}}
     * @response 403 scenario="Not host" {"message": "Only the host can perform this action."}
     */
    public function end(Request $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::Active)
            ->firstOrFail();

        if ($session->host_id !== $request->user()->id) {
            return response()->json([
                'message' => __('sessions.host_only'),
            ], 403);
        }

        $session->participants()
            ->whereNull('finished_at')
            ->update(['finished_at' => now()]);

        $session->update([
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        broadcast(new SessionEnded($session));

        return response()->json([
            'data' => [
                'id' => $session->id,
                'session_code' => $session->join_code,
                'status' => $session->status,
                'completed_at' => $session->completed_at,
            ],
        ]);
    }

    /**
     * Host dashboard
     *
     * Get session details with full participant progress. Only accessible by the session host.
     *
     * @urlParam code string required The 6-character session code. Example: ABC123
     *
     * @response 200 {"data": {"session": {"id": 1, "session_code": "ABC123", "status": "active", "participants_count": 5}, "participants": [{"id": 1, "display_name": "Player1", "total_score": 250, "current_checkpoint_index": 2, "quest_completed_at": null}]}}
     * @response 403 scenario="Not host" {"message": "Only the host can perform this action."}
     */
    public function dashboard(Request $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->firstOrFail();

        if ($session->host_id !== $request->user()->id) {
            return response()->json([
                'message' => __('sessions.host_only'),
            ], 403);
        }

        $session->load(['quest.category', 'host'])
            ->loadCount('participants');

        $participants = $session->participants()
            ->orderByDesc('score')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'display_name' => $p->display_name,
                'total_score' => $p->score,
                'current_checkpoint_index' => $p->current_checkpoint_index,
                'quest_completed_at' => $p->finished_at,
            ]);

        return response()->json([
            'data' => [
                'session' => new SessionResource($session),
                'participants' => $participants,
            ],
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (QuestSession::where('join_code', $code)->exists());

        return $code;
    }
}
