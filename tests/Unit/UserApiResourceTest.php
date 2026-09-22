<?php

use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\UserApiResource;

/**
 * Everything personal comes through here — the player's own quests, their
 * bookmarks, their profile and their right to erase the lot. The cache keys
 * matter as much as the calls: a profile edit that leaves a stale "auth:me"
 * shows the old name until the app is restarted.
 */
function userResource(QuestifyApiClient $client): UserApiResource
{
    return new UserApiResource($client);
}

it('caches the first page of my quests', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/user/quests', [])->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->quests();
    $resource->quests();
});

it('caches each page of my quests separately', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/user/quests', [])->andReturn(['data' => []]);
    $client->shouldReceive('get')->once()->with('/user/quests', ['cursor' => 'page-2'])->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->quests();
    $resource->quests('page-2');
});

it('caches my sessions', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/user/sessions')->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->sessions();
    $resource->sessions();
});

it('caches my bookmarks per page', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->once()->with('/user/favourites', [])->andReturn(['data' => []]);
    $client->shouldReceive('get')->once()->with('/user/favourites', ['cursor' => 'page-2'])->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->favourites();
    $resource->favourites();
    $resource->favourites('page-2');
});

it('refreshes the signed-in account after a profile change', function () {
    // The header and profile screen read auth:me; leaving it cached showed the
    // old name until the app was restarted.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('put')->once()->with('/user/profile', ['name' => 'Kasper'])->andReturn(['data' => []]);

    ApiCache::remember(AuthResource::meCacheKey(), fn () => ['data' => ['name' => 'Old']]);

    userResource($client)->updateProfile(['name' => 'Kasper']);

    expect(ApiCache::remember(AuthResource::meCacheKey(), fn () => ['data' => ['name' => 'Fresh']]))
        ->toBe(['data' => ['name' => 'Fresh']]);
});

it('refreshes my own lists after a profile change', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('get')->twice()->with('/user/sessions')->andReturn(['data' => []]);
    $client->shouldReceive('put')->once()->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->sessions();
    $resource->updateProfile(['name' => 'Kasper']);
    $resource->sessions();
});

it('uploads a new avatar with the real filename', function () {
    // The temporary upload path has no extension, so sending its basename made
    // the API reject a valid photo.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('postMultipart')->once()->withArgs(
        fn ($url, $data, $files) => $url === '/user/profile'
            && ($data['_method'] ?? null) === 'PUT'
            && $files['avatar']['name'] === 'me.png'
    )->andReturn(['data' => []]);

    userResource($client)->updateProfile(['name' => 'Kasper'], '/tmp/phpXk92', 'me.png');
});

it('empties every cache when the account is deleted', function () {
    // Nothing about a deleted account may survive on the device.
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('delete')->once()->with('/user')->andReturn(['data' => []]);
    $client->shouldReceive('get')->twice()->with('/user/sessions')->andReturn(['data' => []]);

    $resource = userResource($client);
    $resource->sessions();
    $resource->deleteAccount();
    $resource->sessions();
});

it('registers a push token with its platform', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/fcm-tokens', [
        'token' => 'abc', 'platform' => 'ios', 'device_name' => 'Kaspers iPhone',
    ])->andReturn(['message' => 'ok']);

    userResource($client)->storeFcmToken('abc', 'ios', 'Kaspers iPhone');
});

it('leaves an unknown device name out of the registration', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('post')->once()->with('/fcm-tokens', [
        'token' => 'abc', 'platform' => 'android',
    ])->andReturn(['message' => 'ok']);

    userResource($client)->storeFcmToken('abc', 'android');
});

it('removes a push token', function () {
    $client = Mockery::mock(QuestifyApiClient::class);
    $client->shouldReceive('delete')->once()->with('/fcm-tokens/abc')->andReturn(['message' => 'ok']);

    userResource($client)->deleteFcmToken('abc');
});

afterEach(fn () => ApiCache::flush());
