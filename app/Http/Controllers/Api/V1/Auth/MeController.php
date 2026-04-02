<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @group Authentication
 */
class MeController extends Controller
{
    /**
     * Current user
     *
     * Get the authenticated user's profile.
     *
     * @response 200 {"data": {"id": 1, "name": "John Doe", "email": "john@example.com", "avatar_path": null, "locale": "en", "is_admin": false, "created_at": "2026-03-12T00:00:00.000000Z"}}
     */
    public function show(Request $request): UserResource
    {
        $user = $request->user();
        $user->loadCount(['sessionParticipations as quests_played_count', 'quests as quests_created_count'])
            ->loadSum('sessionParticipations as total_points', 'score');

        return new UserResource($user);
    }
}
