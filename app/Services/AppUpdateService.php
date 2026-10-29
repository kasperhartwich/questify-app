<?php

namespace App\Services;

use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Cache;

/**
 * Whether a newer version of the app is waiting, from the backend's
 * /app-update, which answers this build from its own channel only.
 * Everything degrades to "nothing to say": an unreachable backend shows no
 * message.
 */
class AppUpdateService
{
    private const DISMISSED_KEY = 'update.dismissed';

    public function __construct(private QuestifyApiClient $apiClient) {}

    /**
     * What the update prompt shows right now, or null for nothing. A required
     * update is always shown; an optional one is not shown again once the
     * player said "later" to that exact version.
     *
     * @return array{required: bool, version: ?string, build: ?int, url: ?string}|null
     */
    public function prompt(): ?array
    {
        try {
            $data = $this->apiClient->get('/app-update')['data'] ?? [];
        } catch (\Throwable) {
            return null;
        }

        $required = ($data['update_required'] ?? false) === true;
        $available = ($data['update_available'] ?? false) === true;

        if (! $required && ! $available) {
            return null;
        }

        $version = is_string($data['latest_version'] ?? null) ? $data['latest_version'] : null;
        $build = is_numeric($data['latest_build'] ?? null) ? (int) $data['latest_build'] : null;

        if (! $required && Cache::get(self::DISMISSED_KEY) === $this->label($version, $build)) {
            return null;
        }

        $url = is_string($data['store_url'] ?? null) && $data['store_url'] !== '' ? $data['store_url'] : null;

        return ['required' => $required, 'version' => $version, 'build' => $build, 'url' => $url];
    }

    /** "Later": the version offered now is not offered again; a newer one is. */
    public function dismiss(?string $version, ?int $build): void
    {
        Cache::forever(self::DISMISSED_KEY, $this->label($version, $build));
    }

    private function label(?string $version, ?int $build): string
    {
        return $build !== null ? "{$version} ({$build})" : (string) $version;
    }
}
