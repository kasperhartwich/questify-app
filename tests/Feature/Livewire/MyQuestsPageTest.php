<?php

use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\QuestApiResource;

function mockDiscoverApiClient(): void
{
    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('list')->andReturn([
        'data' => [],
        'meta' => ['next_cursor' => null, 'prev_cursor' => null],
    ]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);

    app()->instance(QuestifyApiClient::class, $mockClient);
}

it('redirects my-quests to login when not authenticated', function () {
    $this->get('/my-quests')->assertRedirect('/login');
});

it('redirects profile to login when not authenticated', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('renders the discover page', function () {
    mockDiscoverApiClient();
    $this->get('/discover/list')->assertOk();
});

it('discover page shows nearby quests heading', function () {
    mockDiscoverApiClient();
    $this->get('/discover/list')->assertSee('Nearby Quests');
});
