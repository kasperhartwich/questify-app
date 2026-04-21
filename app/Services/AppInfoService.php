<?php

namespace App\Services;

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

        // Serve from cache instantly — never block a request on a network call.
        // The cache is populated after login (QuestifyApiGuard::login) and
        // refreshed by refresh() when needed.
        $this->info = Cache::get(self::CACHE_KEY, $this->defaults());

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
     * @return array<int, string>
     */
    public function enabledSocialProviders(): array
    {
        $methods = $this->authMethods();

        return array_values(array_filter(
            ['google', 'facebook', 'apple', 'microsoft'],
            fn (string $provider): bool => (bool) ($methods[$provider] ?? false),
        ));
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
        return [
            'data' => [
                'auth_methods' => [
                    'email' => true,
                    'phone' => true,
                    'google' => true,
                    'facebook' => true,
                    'apple' => true,
                    'microsoft' => true,
                ],
            ],
        ];
    }
}
