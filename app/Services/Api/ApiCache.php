<?php

namespace App\Services\Api;

use Closure;
use Illuminate\Support\Facades\Cache;

class ApiCache
{
    private const PREFIX = 'api:';

    private const DEFAULT_TTL = 300; // 5 minutes

    /**
     * Track all cache keys so we can flush by prefix.
     * Stored in cache itself under a manifest key.
     */
    private const MANIFEST_KEY = 'api:_manifest';

    private static bool $bypass = false;

    /**
     * Cache an API response with the given key and TTL.
     *
     * @return array<string, mixed>
     */
    public static function remember(string $key, Closure $callback, int $ttl = self::DEFAULT_TTL): array
    {
        $fullKey = self::PREFIX.$key;

        if (self::$bypass) {
            self::$bypass = false;
            Cache::forget($fullKey);
        }

        $result = Cache::remember($fullKey, $ttl, $callback);

        self::trackKey($fullKey);

        return $result;
    }

    /**
     * Forget specific cache keys.
     */
    public static function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget(self::PREFIX.$key);
            self::untrackKey(self::PREFIX.$key);
        }
    }

    /**
     * Forget all cache keys that start with the given prefix.
     */
    public static function forgetPrefix(string $prefix): void
    {
        $fullPrefix = self::PREFIX.$prefix;
        $manifest = self::getManifest();

        foreach ($manifest as $key) {
            if (str_starts_with($key, $fullPrefix)) {
                Cache::forget($key);
                self::untrackKey($key);
            }
        }
    }

    /**
     * Flush all API cache entries.
     */
    public static function flush(): void
    {
        $manifest = self::getManifest();

        foreach ($manifest as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::MANIFEST_KEY);
    }

    /**
     * Bypass cache for the next remember() call.
     */
    public static function fresh(): void
    {
        self::$bypass = true;
    }

    /**
     * @return array<int, string>
     */
    private static function getManifest(): array
    {
        return Cache::get(self::MANIFEST_KEY, []);
    }

    private static function trackKey(string $key): void
    {
        $manifest = self::getManifest();

        if (! in_array($key, $manifest, true)) {
            $manifest[] = $key;
            Cache::put(self::MANIFEST_KEY, $manifest);
        }
    }

    private static function untrackKey(string $key): void
    {
        $manifest = self::getManifest();
        $manifest = array_values(array_filter($manifest, fn (string $k) => $k !== $key));
        Cache::put(self::MANIFEST_KEY, $manifest);
    }
}
