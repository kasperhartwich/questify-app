<?php

use App\Models\User;
use App\Services\Api\ApiCache;
use App\Services\Api\QuestifyApiClient;
use Illuminate\Support\Facades\Http;

/**
 * A bookmark must read the same everywhere. Toggling used to leave the cached
 * nearby response untouched, so the discover map kept showing the old state
 * until the cache expired.
 */
it('drops every cached view of quests when a bookmark is toggled', function () {
    Http::fake(['*' => Http::response(['data' => ['is_favourited' => true]], 200)]);

    ApiCache::remember('quests:list:abc', fn () => ['data' => 'stale list']);
    ApiCache::remember('quests:nearby:xyz', fn () => ['data' => 'stale nearby']);
    ApiCache::remember('quests:show:1', fn () => ['data' => 'stale detail']);
    ApiCache::remember('user:favourites:1', fn () => ['data' => 'stale favourites']);

    app(QuestifyApiClient::class)->quests()->toggleFavourite(1);

    foreach (['quests:list:abc', 'quests:nearby:xyz', 'quests:show:1', 'user:favourites:1'] as $key) {
        expect(cache()->has($key))->toBeFalse("$key survived the toggle");
    }
});

it('marks the app dirty from both bookmark buttons', function () {
    $user = User::factory()->create();
    mockFullApiClient();

    // The list card and the quest page must both flag the change, or the other
    // screen keeps its cached snapshot after a wire:navigate back.
    $list = $this->actingAs($user)->get('/discover/list')->assertOk()->getContent();
    expect($list)->toContain('questifyFavouriteChanged()');

    $detail = $this->actingAs($user)->get('/quests/1')->assertOk()->getContent();
    expect($detail)->toContain('questifyFavouriteChanged()');
});

it('refreshes every component once the app is marked dirty', function () {
    mockFullApiClient();

    $content = $this->actingAs(User::factory()->create())
        ->get('/discover/list')
        ->assertOk()
        ->getContent();

    expect($content)->toContain('livewire:navigated')
        ->and($content)->toContain('questify:favourites-dirty');
});
