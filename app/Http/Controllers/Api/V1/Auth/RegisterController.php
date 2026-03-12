<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 */
class RegisterController extends Controller
{
    /**
     * Register
     *
     * Create a new user account and return an authentication token.
     *
     * @unauthenticated
     *
     * @response 201 {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "avatar_path": null, "locale": "en", "is_admin": false, "created_at": "2026-03-12T00:00:00.000000Z"}, "token": "1|abcdef123456"}
     * @response 422 scenario="Validation error" {"message": "The email has already been taken.", "errors": {"email": ["The email has already been taken."]}}
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
