<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User Profile
 */
class UserProfileController extends Controller
{
    /**
     * Update profile
     *
     * Update the authenticated user's profile information.
     *
     * @response 200 {"data": {"id": 1, "name": "Updated Name", "email": "john@example.com", "locale": "da"}}
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user()->fresh());
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

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => __('general.account_deleted')]);
    }
}
