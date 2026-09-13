<?php

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\QuestApiResource;

/**
 * This resource decides what is cached and, more importantly, what is thrown
 * away. A write that forgets to invalidate leaves a stale bookmark or a
 * deleted quest on screen — the class of bug that only shows up on a device.
 */
function questResource(QuestifyApiClient $client): QuestApiResource
{
    return new QuestApiResource($client);
}

// --- Reads ---

it('asks the api once and serves the second call from cache', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/quests', [])->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->list();
    $resource->list();
});

it('caches each set of filters separately', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/quests', ['difficulty' => 'easy'])->andReturn(['data' => []]);
    $client->shouldReceive('get')->once()->with('/quests', ['difficulty' => 'hard'])->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->list(['difficulty' => 'easy']);
    $resource->list(['difficulty' => 'hard']);
});

it('drops empty filters rather than sending them', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/quests', ['difficulty' => 'easy'])->andReturn(['data' => []]);

    questResource($client)->list(['difficulty' => 'easy', 'search' => '', 'category_id' => null]);
});

it('sends the position with a nearby search', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()
        ->with('/quests/nearby', ['latitude' => 55.6761, 'longitude' => 12.5683, 'radius' => 25])
        ->andReturn(['data' => []]);

    questResource($client)->nearby(55.6761, 12.5683, ['radius' => 25]);
});

it('caches one quest per id', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/quests/1')->andReturn(['data' => ['id' => 1]]);
    $client->shouldReceive('get')->once()->with('/quests/2')->andReturn(['data' => ['id' => 2]]);

    $resource = questResource($client);
    $resource->show(1);
    $resource->show(1);
    $resource->show(2);
});

// --- Writes must invalidate ---

it('forgets every cached view of quests after a bookmark is toggled', function () {
    // The discover map reads quests:nearby:, which used to survive a toggle
    // and kept showing the old bookmark state.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/quests/1')->andReturn(['data' => ['id' => 1]]);
    $client->shouldReceive('post')->once()->with('/quests/1/favourite')->andReturn(['data' => ['is_favourited' => true]]);

    $resource = questResource($client);
    $resource->show(1);
    $resource->toggleFavourite(1);
    $resource->show(1);
});

it('forgets the cached lists after a quest is created', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/quests', [])->andReturn(['data' => []]);
    $client->shouldReceive('post')->once()->with('/quests', ['title' => 'New'])->andReturn(['data' => ['id' => 9]]);

    $resource = questResource($client);
    $resource->list();
    $resource->store(['title' => 'New']);
    $resource->list();
});

it('forgets the cached lists after a quest is changed', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/quests/1')->andReturn(['data' => ['id' => 1]]);
    $client->shouldReceive('put')->once()->with('/quests/1', ['title' => 'Renamed'])->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->show(1);
    $resource->update(1, ['title' => 'Renamed']);
    $resource->show(1);
});

it('forgets the cached lists after a quest is archived', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/quests', [])->andReturn(['data' => []]);
    $client->shouldReceive('delete')->once()->with('/quests/1')->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->list();
    $resource->destroy(1);
    $resource->list();
});

it('forgets the cached lists after a quest is published', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/quests', [])->andReturn(['data' => []]);
    $client->shouldReceive('post')->once()->with('/quests/1/publish')->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->list();
    $resource->publish(1);
    $resource->list();
});

it('refreshes only the rated quest, not every list', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/quests', [])->andReturn(['data' => []]);
    $client->shouldReceive('get')->twice()->with('/quests/1')->andReturn(['data' => ['id' => 1]]);
    $client->shouldReceive('post')->once()->with('/quests/1/rate', ['rating' => 5])->andReturn(['data' => []]);

    $resource = questResource($client);
    $resource->list();
    $resource->show(1);
    $resource->rate(1, 5);
    $resource->show(1);
    $resource->list();
});

it('leaves a blank comment out of a rating', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/quests/1/rate', ['rating' => 4])->andReturn(['data' => []]);

    questResource($client)->rate(1, 4);
});

it('sends a reason when a quest is reported', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/quests/1/flag', ['reason' => 'Inappropriate'])->andReturn(['data' => []]);

    questResource($client)->flag(1, 'Inappropriate');
});

// --- Cover images ---

it('uploads the cover image alongside a new quest', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('postMultipart')->once()->with(
        '/quests',
        ['title' => 'New'],
        ['cover_image' => ['path' => '/tmp/upload', 'name' => 'cover.jpg']],
    )->andReturn(['data' => []]);

    questResource($client)->store(['title' => 'New'], '/tmp/upload', 'cover.jpg');
});

it('keeps the original filename so the api can validate the image', function () {
    // Temporary upload paths have no extension; sending the path's basename
    // made the API reject a perfectly good photo.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('postMultipart')->once()->withArgs(function ($url, $data, $files) {
        return $files['cover_image']['name'] === 'holiday.png';
    })->andReturn(['data' => []]);

    questResource($client)->store(['title' => 'New'], '/tmp/php7Xk2', 'holiday.png');
});

it('spoofs a put when updating with a new cover image', function () {
    // Multipart bodies cannot be sent as PUT, so the method is tunnelled.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('postMultipart')->once()->withArgs(
        fn ($url, $data) => $url === '/quests/1' && ($data['_method'] ?? null) === 'PUT'
    )->andReturn(['data' => []]);

    questResource($client)->update(1, ['title' => 'Renamed'], '/tmp/upload', 'cover.jpg');
});

afterEach(fn () => ApiCache::flush());
