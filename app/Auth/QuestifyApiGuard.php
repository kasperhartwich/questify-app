<?php

namespace App\Auth;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\AppInfoService;
use App\Services\TokenStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;

class QuestifyApiGuard implements Guard
{
    private ?ApiTokenUser $user = null;

    private bool $resolved = false;

    public function __construct(
        private QuestifyApiClient $client,
        private Session $session,
    ) {}

    public function check(): bool
    {
        return $this->user !== null || TokenStorage::has();
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;

        $userData = $this->session->get('questify_user');
        if ($userData) {
            $this->user = new ApiTokenUser($userData);
        }

        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->session->get('questify_user.id');
    }

    public function validate(array $credentials = []): bool
    {
        try {
            $response = $this->client->auth()->login(
                $credentials['email'],
                $credentials['password'],
            );

            $this->login($response['data']['user'], $response['data']['token']);

            return true;
        } catch (ApiAuthenticationException) {
            return false;
        }
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user instanceof ApiTokenUser ? $user : null;
        $this->resolved = true;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    public function login(array $userData, string $token): void
    {
        ApiCache::flush();

        TokenStorage::set($token);
        $this->session->put('questify_user', $userData);
        $this->session->regenerate();

        // Remember that user has logged in before (for welcome screen redirect)
        cookie()->queue(cookie()->forever('has_logged_in', '1'));

        $this->user = new ApiTokenUser($userData);
        $this->resolved = true;

        // Prefetch commonly needed data into cache
        try {
            $this->client->auth()->me();
            $this->client->categories()->list();
            app(AppInfoService::class)->refresh();
        } catch (\Throwable) {
            // Non-critical — pages will fetch on demand
        }
    }

    public function logout(): void
    {
        try {
            $this->client->auth()->logout();
        } catch (\Throwable) {
            // Ignore API errors during logout
        }

        ApiCache::flush();
        TokenStorage::forget();
        $this->session->forget('questify_user');
        $this->session->invalidate();
        $this->session->regenerateToken();

        $this->user = null;
        $this->resolved = true;
    }
}
