<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

/**
 * @group Authentication
 */
class ForgotPasswordController extends Controller
{
    /**
     * Forgot password
     *
     * Send a password reset link to the given email address.
     *
     * @unauthenticated
     *
     * @response 200 {"message": "We have emailed your password reset link."}
     * @response 422 scenario="Invalid email" {"message": "We can't find a user with that email address."}
     */
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 422);
    }
}
