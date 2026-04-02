<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;

class UserApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function quests(?string $cursor = null): array
    {
        return $this->client->get('/user/quests', array_filter(['cursor' => $cursor]));
    }

    public function sessions(): array
    {
        return $this->client->get('/user/sessions');
    }

    public function favourites(?string $cursor = null): array
    {
        return $this->client->get('/user/favourites', array_filter(['cursor' => $cursor]));
    }

    /**
     * @param  array{name?: string, locale?: string}  $data
     * @param  string|null  $avatarPath  Local file path for avatar upload
     */
    public function updateProfile(array $data, ?string $avatarPath = null): array
    {
        if ($avatarPath) {
            return $this->client->postMultipart('/user/profile', array_merge($data, ['_method' => 'PUT']), [
                'avatar' => [
                    'path' => $avatarPath,
                    'name' => basename($avatarPath),
                ],
            ]);
        }

        return $this->client->put('/user/profile', $data);
    }

    public function deleteAccount(): array
    {
        return $this->client->delete('/user');
    }
}
