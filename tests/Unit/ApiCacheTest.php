<?php

use App\Services\Api\ApiCache;
use Illuminate\Support\Facades\Cache;

/**
 * The cache is what makes screens feel instant, and its prefix invalidation is
 * what stops them lying. A key that survives a write shows the player a stale
 * bookmark or a quest they already archived.
 */
beforeEach(fn () => ApiCache::flush());

it('calls the backend once and serves the rest from cache', function () {
    $calls = 0;
    $fetch = function () use (&$calls) {
        $calls++;

        return ['data' => ['id' => 1]];
    };

    ApiCache::remember('quests:show:1', $fetch);
    ApiCache::remember('quests:show:1', $fetch);

    expect($calls)->toBe(1);
});

it('returns what the backend gave it', function () {
    expect(ApiCache::remember('quests:show:1', fn () => ['data' => ['id' => 1]]))
        ->toBe(['data' => ['id' => 1]]);
});

it('namespaces its keys so nothing else in the cache collides', function () {
    ApiCache::remember('quests:show:1', fn () => ['data' => []]);

    expect(Cache::has('api:quests:show:1'))->toBeTrue();
});

it('forgets one key without touching its neighbours', function () {
    ApiCache::remember('quests:show:1', fn () => ['data' => ['id' => 1]]);
    ApiCache::remember('quests:show:2', fn () => ['data' => ['id' => 2]]);

    ApiCache::forget('quests:show:1');

    expect(Cache::has('api:quests:show:1'))->toBeFalse()
        ->and(Cache::has('api:quests:show:2'))->toBeTrue();
});

it('forgets several keys at once', function () {
    ApiCache::remember('a', fn () => ['data' => []]);
    ApiCache::remember('b', fn () => ['data' => []]);

    ApiCache::forget('a', 'b');

    expect(Cache::has('api:a'))->toBeFalse()
        ->and(Cache::has('api:b'))->toBeFalse();
});

it('forgets a whole family of keys by prefix', function () {
    // This is what a bookmark toggle relies on: the list, the map and the
    // detail view all have to drop together or they disagree.
    ApiCache::remember('quests:list:abc', fn () => ['data' => []]);
    ApiCache::remember('quests:nearby:def', fn () => ['data' => []]);
    ApiCache::remember('user:sessions', fn () => ['data' => []]);

    ApiCache::forgetPrefix('quests:');

    expect(Cache::has('api:quests:list:abc'))->toBeFalse()
        ->and(Cache::has('api:quests:nearby:def'))->toBeFalse()
        ->and(Cache::has('api:user:sessions'))->toBeTrue();
});

it('empties everything it owns', function () {
    ApiCache::remember('quests:list', fn () => ['data' => []]);
    ApiCache::remember('user:sessions', fn () => ['data' => []]);

    ApiCache::flush();

    expect(Cache::has('api:quests:list'))->toBeFalse()
        ->and(Cache::has('api:user:sessions'))->toBeFalse();
});

it('leaves cache entries that are not ours alone when flushing', function () {
    Cache::put('some_other_thing', 'keep me');
    ApiCache::remember('quests:list', fn () => ['data' => []]);

    ApiCache::flush();

    expect(Cache::get('some_other_thing'))->toBe('keep me');
});

it('refetches once when asked for something fresh', function () {
    $calls = 0;
    $fetch = function () use (&$calls) {
        $calls++;

        return ['data' => []];
    };

    ApiCache::remember('quests:list', $fetch);
    ApiCache::fresh();
    ApiCache::remember('quests:list', $fetch);
    ApiCache::remember('quests:list', $fetch);

    // Two fetches: the first, and the one that bypassed. The third is cached
    // again — fresh() applies to the next call only.
    expect($calls)->toBe(2);
});

it('stops tracking a key it has forgotten', function () {
    // A manifest that keeps growing makes every prefix flush slower.
    ApiCache::remember('quests:list', fn () => ['data' => []]);
    ApiCache::forget('quests:list');

    expect(Cache::get('api:_manifest', []))->not->toContain('api:quests:list');
});

it('tracks each key once however often it is read', function () {
    ApiCache::remember('quests:list', fn () => ['data' => []]);
    ApiCache::remember('quests:list', fn () => ['data' => []]);

    expect(collect(Cache::get('api:_manifest', []))->filter(fn ($k) => $k === 'api:quests:list'))
        ->toHaveCount(1);
});
