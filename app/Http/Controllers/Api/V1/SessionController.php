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

class SessionController extends Controller
{
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

    public function show(string $code): SessionResource
    {
        $session = QuestSession::where('join_code', $code)
            ->with(['quest.category', 'host', 'participants.user'])
            ->withCount('participants')
            ->firstOrFail();

        return new SessionResource($session);
    }

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
