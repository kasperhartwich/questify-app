<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSessionController extends Controller
{
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
