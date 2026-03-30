<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 */
class LoginController extends Controller
{
    /**
     * Login
     *
     * Authenticate a user and return an API token.
     *
     * @unauthenticated
     *
     * @response 200 {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "avatar_path": null, "locale": "en", "is_admin": false, "created_at": "2026-03-12T00:00:00.000000Z"}, "token": "1|abcdef123456"}
     * @response 422 scenario="Invalid credentials" {"message": "These credentials do not match our records."}
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     *
     * @response 200 {"message": "Logged out successfully."}
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logout'),
        ]);
    }
}
