<?php

use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\UserApiResource;

it('guest is redirected to login from protected routes', function (string $route) {
    $this->get($route)->assertRedirect('/login');
})->with([
    '/create',
    '/profile',
    '/my-quests',
    '/my-quests/created',
]);

it('authenticated user can access protected routes', function (string $route) {
    $user = User::factory()->create();

    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('list')->andReturn(['data' => [], 'meta' => ['next_cursor' => null, 'prev_cursor' => null]]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => []]);

    $mockUser = Mockery::mock(UserApiResource::class);
    $mockUser->shouldReceive('quests')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);
    $mockUser->shouldReceive('sessions')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('user')->andReturn($mockUser);

    $this->app->instance(QuestifyApiClient::class, $mockClient);

    $this->actingAs($user)->get($route)->assertOk();
})->with([
    '/profile',
    '/my-quests',
    '/my-quests/created',
    '/create',
]);
