<?php

namespace App\Services;

use App\Enums\SocialProvider;
use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppInfoService
{
    private const CACHE_KEY = 'app_info';

    private const CACHE_TTL_SECONDS = 300;

    /** @var array<string, mixed>|null */
    private ?array $info = null;

    public function __construct(private QuestifyApiClient $apiClient) {}

    /**
     * Fetch app info from the API, falling back to cache, then defaults.
     *
     * Uses Cache::remember() so the first request on cold start serves
     * cached data instantly instead of blocking on a network call.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if ($this->info !== null) {
            return $this->info;
        }

        // Serve from cache when populated (login seeds it, refresh() keeps it
        // warm). On a cache miss — e.g. the login screen on a fresh install —
        // fetch once so the UI reflects what the backend actually offers; only
        // fall back to defaults when the backend is unreachable.
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached === null) {
            $this->refresh();
            $cached = Cache::get(self::CACHE_KEY);
        }

        $this->info = $cached ?? $this->defaults();

        return $this->info;
    }

    /**
     * Get the list of enabled auth methods.
     *
     * @return array<string, bool>
     */
    public function authMethods(): array
    {
        $info = $this->get();

        return $info['data']['auth_methods'] ?? $this->defaults()['data']['auth_methods'];
    }

    /**
     * Check if a specific auth method is enabled.
     */
    public function isAuthMethodEnabled(string $method): bool
    {
        return (bool) ($this->authMethods()[$method] ?? false);
    }

    /**
     * Get the list of enabled social providers.
     *
     * The backend is the source of truth: OAuth runs on the backend (the app
     * opens its /auth/{provider}/redirect and receives the token back via the
     * questify:// deep link), and its /info already reflects which providers
     * hold credentials — so a provider is offered exactly when the backend
     * says it is enabled.
     *
     * @return array<int, string>
     */
    public function enabledSocialProviders(): array
    {
        $methods = $this->authMethods();

        return array_values(array_filter(
            array_map(fn (SocialProvider $provider): string => $provider->value, SocialProvider::cases()),
            fn (string $provider): bool => (bool) ($methods[$provider] ?? false),
        ));
    }

    /**
     * Absolute backend URL that starts the OAuth flow for a provider.
     */
    public function socialRedirectUrl(string $provider): string
    {
        return rtrim(config('services.questify.url'), '/')."/auth/{$provider}/redirect";
    }

    /**
     * Fetch fresh data from the API and update the cache.
     * Called during login to seed the cache.
     */
    public function refresh(): void
    {
        try {
            $fresh = $this->apiClient->get('/info');
            Cache::put(self::CACHE_KEY, $fresh, self::CACHE_TTL_SECONDS);
            $this->info = $fresh;
        } catch (\Throwable $e) {
            Log::warning('Failed to refresh /info from API', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Default info when both API and cache are unavailable.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        // Offline fallback: email/phone always work locally, but social login
        // needs the backend — never offer providers we can't confirm.
        return [
            'data' => [
                'auth_methods' => [
                    'email' => true,
                    'phone' => true,
                    'google' => false,
                    'facebook' => false,
                    'apple' => false,
                    'microsoft' => false,
                ],
            ],
        ];
    }
}
