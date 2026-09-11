<?php

namespace App\Http\Controllers\Auth;

use App\Auth\QuestifyApiGuard;
use App\Http\Controllers\Controller;
use App\Services\Api\QuestifyApiClient;
use App\Services\TokenStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Completes a backend-driven social login. The backend's OAuth callback
 * redirects to questify://auth/callback?token={sanctum token}, which the
 * native shell opens as this route. We validate the token against the API
 * and log the user into the questify-api guard.
 */
class SocialCallbackController extends Controller
{
    public function __invoke(Request $request, QuestifyApiClient $client): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return redirect('/login');
        }

        TokenStorage::set($token);

        try {
            $userData = $client->auth()->me()['data'] ?? null;
        } catch (\Throwable) {
            $userData = null;
        }

        if (! $userData) {
            TokenStorage::forget();

            return redirect('/login');
        }

        $guard = auth()->guard();

        if ($guard instanceof QuestifyApiGuard) {
            $guard->login($userData, $token);
        }

        return redirect($this->safeRedirectTarget($request));
    }

    /**
     * Only internal paths are honoured — an absolute URL in the deep link
     * must never redirect the user off-app.
     */
    private function safeRedirectTarget(Request $request): string
    {
        $target = (string) $request->query('redirect', '');

        if ($target !== '' && str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return $target;
        }

        return '/discover/list';
    }
}
