<?php

use App\Models\User;
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
    $mockQuests->shouldReceive('nearby')->andReturn(['data' => []]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    app()->instance(QuestifyApiClient::class, $mockClient);
}

it('redirects my-quests to welcome when not authenticated', function () {
    $this->get('/my-quests')->assertRedirect('/');
});

it('redirects profile to welcome when not authenticated', function () {
    $this->get('/profile')->assertRedirect('/');
});

it('renders the discover page', function () {
    mockDiscoverApiClient();
    $this->actingAs(User::factory()->create())
        ->get('/discover/list')->assertOk();
});

it('discover page shows nearby quests heading', function () {
    mockDiscoverApiClient();
    $this->actingAs(User::factory()->create())
        ->get('/discover/list')->assertSee('Nearby Quests');
});
