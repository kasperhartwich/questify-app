<?php

namespace App\Livewire\Concerns;

use App\Services\Api\QuestifyApiClient;

trait WithApiClient
{
    public function getApiProperty(): QuestifyApiClient
    {
        return app(QuestifyApiClient::class);
    }
}
