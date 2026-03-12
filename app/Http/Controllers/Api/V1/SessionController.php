<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SessionStatus;
use App\Events\ParticipantJoined;
use App\Events\SessionEnded;
use App\Events\SessionStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinSessionRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Resources\SessionParticipantResource;
use App\Http\Resources\SessionResource;
use App\Models\Quest;
use App\Models\QuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Create a new quest session with a unique 6-character join code.
     *
     * @response 201 {"session": {"id": 1, "join_code": "ABC123", "status": "waiting", "play_mode": "solo", "quest": {"id": 1, "title": "City Walk"}, "participants_count": 0}}
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

        $session->load(['quest.category', 'host']);
        $session->loadCount('participants');

        return response()->json([
            'session' => new SessionResource($session),
        ], 201);
    }

    /**
     * Show session
     *
     * Get session details by join code. Publicly accessible.
     *
     * @unauthenticated
     *
     * @urlParam code string required The 6-character join code. Example: ABC123
     *
     * @response 200 {"data": {"id": 1, "join_code": "ABC123", "status": "waiting", "quest": {"id": 1, "title": "City Walk"}, "participants": [], "participants_count": 0}}
     */
    public function show(string $code): SessionResource
    {
        $session = QuestSession::where('join_code', $code)
            ->with(['quest.category', 'host', 'participants.user'])
            ->withCount('participants')
            ->firstOrFail();

        return new SessionResource($session);
    }

    /**
     * Join session
     *
     * Join a waiting session as a participant. Each player can only be in one active session at a time.
     *
     * @urlParam code string required The 6-character join code. Example: ABC123
     *
     * @response 201 {"message": "Joined session.", "participant": {"id": 1, "display_name": "Player1", "score": 0}}
     * @response 409 scenario="Already in session" {"message": "You are already in an active session."}
     * @response 422 scenario="Session full" {"message": "This session is full."}
     */
    public function join(JoinSessionRequest $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::Waiting)
            ->firstOrFail();

        // Enforce one active session per player
        $hasActiveSession = $request->user()->sessionParticipations()
            ->whereHas('questSession', fn ($query) => $query->active())
            ->exists();

        if ($hasActiveSession) {
            return response()->json([
                'message' => __('sessions.already_in_session'),
            ], 409);
        }

        // Check max participants
        if ($session->quest->max_participants) {
            $currentCount = $session->participants()->count();
            if ($currentCount >= $session->quest->max_participants) {
                return response()->json([
                    'message' => __('sessions.session_full'),
                ], 422);
            }
        }

        $participant = $session->participants()->create([
            'user_id' => $request->user()->id,
            'display_name' => $request->validated('display_name'),
        ]);

        $participant->load('user');

        broadcast(new ParticipantJoined($session->join_code, $participant))->toOthers();

        return response()->json([
            'message' => __('sessions.joined'),
            'participant' => new SessionParticipantResource($participant),
        ], 201);
    }

    /**
     * Start session
     *
     * Start a waiting session. Only the host can start the session. Broadcasts SessionStarted event.
     *
     * @urlParam code string required The 6-character join code. Example: ABC123
     *
     * @response 200 {"message": "Session started.", "session": {"id": 1, "status": "in_progress"}}
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
            'status' => SessionStatus::InProgress,
            'started_at' => now(),
        ]);

        broadcast(new SessionStarted($session));

        return response()->json([
            'message' => __('sessions.started'),
            'session' => new SessionResource($session->load(['quest.category', 'host'])->loadCount('participants')),
        ]);
    }

    /**
     * End session
     *
     * End an in-progress session. Marks unfinished participants as DNF. Only the host can end the session.
     *
     * @urlParam code string required The 6-character join code. Example: ABC123
     *
     * @response 200 {"message": "Session ended.", "session": {"id": 1, "status": "completed"}}
     * @response 403 scenario="Not host" {"message": "Only the host can perform this action."}
     */
    public function end(Request $request, string $code): JsonResponse
    {
        $session = QuestSession::where('join_code', $code)
            ->where('status', SessionStatus::InProgress)
            ->firstOrFail();

        if ($session->host_id !== $request->user()->id) {
            return response()->json([
                'message' => __('sessions.host_only'),
            ], 403);
        }

        // Mark unfinished participants as DNF (set finished_at to now)
        $session->participants()
            ->whereNull('finished_at')
            ->update(['finished_at' => now()]);

        $session->update([
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        broadcast(new SessionEnded($session));

        return response()->json([
            'message' => __('sessions.ended'),
            'session' => new SessionResource($session->load(['quest.category', 'host', 'participants.user'])->loadCount('participants')),
        ]);
    }

    /**
     * Host dashboard
     *
     * Get session details with full participant progress. Only accessible by the session host.
     *
     * @urlParam code string required The 6-character join code. Example: ABC123
     *
     * @response 200 {"session": {"id": 1, "join_code": "ABC123", "status": "in_progress", "participants": [], "participants_count": 5}}
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

        $session->load([
            'quest.category',
            'quest.checkpoints.questions',
            'host',
            'participants.user',
            'participants.checkpointProgress',
        ])->loadCount('participants');

        return response()->json([
            'session' => new SessionResource($session),
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
