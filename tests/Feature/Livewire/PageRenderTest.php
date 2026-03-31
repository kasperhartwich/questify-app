<?php

use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\QuestApiResource;

beforeEach(function () {
    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('list')->andReturn(['data' => [], 'meta' => ['next_cursor' => null, 'prev_cursor' => null]]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);

    $this->app->instance(QuestifyApiClient::class, $mockClient);
});

it('renders welcome page for guests', function () {
    $this->get('/')->assertOk();
});

it('renders quest list page', function () {
    $this->get('/discover/list')->assertOk();
});

it('renders profile page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});
