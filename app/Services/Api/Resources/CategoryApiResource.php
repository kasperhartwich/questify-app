<?php

namespace App\Services\Api\Resources;

use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Cache;

class CategoryApiResource
{
    public function __construct(private QuestifyApiClient $client) {}

    public function list(): array
    {
        return Cache::remember('api_categories', 3600, function () {
            return $this->client->get('/categories');
        });
    }
}
