<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group User Profile
 */
class UserProfileController extends Controller
{
    /**
     * Update profile
     *
     * Update the authenticated user's name, avatar, and locale. Email cannot be changed.
     *
     * @bodyParam name string optional The user's name. Example: John Doe
     * @bodyParam avatar file optional The user's avatar image. Max 2MB.
     * @bodyParam locale string optional The user's locale (en or da). Example: da
     *
     * @response 200 {"data": {"id": 1, "name": "Updated Name", "avatar_url": "...", "locale": "da"}}
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle avatar file upload
        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            unset($data['avatar']);
        } else {
            unset($data['avatar']);
        }

        $user->update($data);

        return response()->json([
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Delete account
     *
     * Permanently delete the authenticated user's account and all associated tokens (GDPR).
     *
     * @response 200 {"message": "Account deleted."}
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            // Anonymize participations (preserve gameplay data)
            $user->sessionParticipations()->update([
                'user_id' => null,
                'display_name' => 'Deleted User',
            ]);

            $user->socialAccounts()->delete();
            $user->activityLogs()->delete();
            $user->favouriteQuests()->detach();
            $user->tokens()->delete();

            $user->update([
                'name' => 'Deleted User',
                'email' => null,
                'phone_number' => null,
                'avatar_path' => null,
            ]);
            $user->delete();
        });

        return response()->json(['message' => __('general.account_deleted')]);
    }
}
