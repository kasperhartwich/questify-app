<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group User Profile
 */
class ActivityController extends Controller
{
    /**
     * Recent activity
     *
     * Get the authenticated user's activity feed, cursor-paginated.
     *
     * @response 200 {"data": [{"id": 1, "type": "quest_completed", "title": "Completed City Walk quest", "subtitle": "1st · 2,340 pts", "icon": "checkmark", "metadata": {"quest_title": "City Walk", "score": 2340, "placement": 1}, "created_at": "2026-04-01T12:00:00.000000Z"}]}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $activities = $request->user()
            ->activityLogs()
            ->latest()
            ->cursorPaginate(15);

        return ActivityResource::collection($activities);
    }
}
