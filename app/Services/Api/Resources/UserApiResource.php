<?php

namespace App\Services\Api\Resources;

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;

class UserApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function quests(?string $cursor = null): array
    {
        $key = 'user:quests:'.($cursor ?? 'first');

        return ApiCache::remember($key, fn () => $this->client->get('/user/quests', array_filter(['cursor' => $cursor])));
    }

    public function sessions(): array
    {
        return ApiCache::remember('user:sessions', fn () => $this->client->get('/user/sessions'));
    }

    public function favourites(?string $cursor = null): array
    {
        $key = 'user:favourites:'.($cursor ?? 'first');

        return ApiCache::remember($key, fn () => $this->client->get('/user/favourites', array_filter(['cursor' => $cursor])));
    }

    /**
     * @param  array{name?: string, locale?: string}  $data
     * @param  string|null  $avatarPath  Local file path for avatar upload
     */
    public function updateProfile(array $data, ?string $avatarPath = null): array
    {
        $result = $avatarPath
            ? $this->client->postMultipart('/user/profile', array_merge($data, ['_method' => 'PUT']), [
                'avatar' => [
                    'path' => $avatarPath,
                    'name' => basename($avatarPath),
                ],
            ])
            : $this->client->put('/user/profile', $data);

        ApiCache::forget('auth:me');
        ApiCache::forgetPrefix('user:');

        return $result;
    }

    public function deleteAccount(): array
    {
        $result = $this->client->delete('/user');

        ApiCache::flush();

        return $result;
    }

    /**
     * @return array{message: string}
     */
    public function storeFcmToken(string $token, string $platform, ?string $deviceName = null): array
    {
        return $this->client->post('/fcm-tokens', array_filter([
            'token' => $token,
            'platform' => $platform,
            'device_name' => $deviceName,
        ]));
    }

    /**
     * @return array{message: string}
     */
    public function deleteFcmToken(string $token): array
    {
        return $this->client->delete("/fcm-tokens/{$token}");
    }
}
