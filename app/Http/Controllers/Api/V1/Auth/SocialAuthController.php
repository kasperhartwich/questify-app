<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Enum;
use Laravel\Socialite\Facades\Socialite;

/**
 * @group Social Authentication
 *
 * OAuth social login endpoints. These are web routes that handle the OAuth redirect flow.
 */
class SocialAuthController extends Controller
{
    /**
     * Redirect to provider
     *
     * Redirect the user to the OAuth provider's authorization page.
     *
     * @unauthenticated
     *
     * @urlParam provider string required The social provider. Example: google
     *
     * @response 302 scenario="Redirect to provider"
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Provider callback
     *
     * Handle the callback from the OAuth provider, create or update the user, and redirect to the app with a token.
     *
     * @unauthenticated
     *
     * @urlParam provider string required The social provider. Example: google
     *
     * @response 302 scenario="Redirect to app with token"
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        $socialUser = Socialite::driver($provider)->user();

        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'token_expires_at' => isset($socialUser->expiresIn)
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);

            $user = $socialAccount->user;
        } else {
            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar_path' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'token_expires_at' => isset($socialUser->expiresIn)
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return redirect()->away("questify://auth/callback?token={$token}");
    }

    private function validateProvider(string $provider): void
    {
        request()->merge(['provider' => $provider]);

        request()->validate([
            'provider' => ['required', new Enum(SocialProvider::class)],
        ]);
    }
}
