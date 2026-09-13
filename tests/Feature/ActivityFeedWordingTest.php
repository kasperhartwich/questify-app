<?php

use App\Models\Quest;
use App\Models\User;
use App\Services\ActivityLogService;

/**
 * The activity feed is prose assembled from a type key and a metadata bag.
 * Every branch writes a different sentence, and an unhandled key falls back to
 * the raw type name — which is how internal wording reaches a player.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->quest = Quest::factory()->create(['title' => 'Copenhagen History Hunt']);
});

function activityFor(string $key, array $metadata = []): array
{
    app(ActivityLogService::class)->log(
        test()->user,
        $key,
        test()->quest,
        array_merge(['quest_title' => test()->quest->title], $metadata),
    );

    return test()->actingAs(test()->user)
        ->getJson('/api/v1/user/activities')
        ->assertOk()
        ->json('data.0');
}

it('writes a sentence for each kind of activity', function (string $key, string $title) {
    expect(activityFor($key)['title'])->toBe($title);
})->with([
    'completed' => ['quest_completed', 'Completed Copenhagen History Hunt quest'],
    'published' => ['quest_published', 'Published new quest'],
    'created' => ['quest_created', 'Created new quest'],
    'shared' => ['quest_shared', 'Shared a quest'],
    'rated' => ['quest_rated', 'Rated a quest'],
    'favourited' => ['quest_favourited', 'Favourited a quest'],
]);

it('names the quest under most activities', function () {
    expect(activityFor('quest_created')['subtitle'])->toBe('Copenhagen History Hunt');
});

it('shows placement and score for a completed quest', function () {
    expect(activityFor('quest_completed', ['placement' => 1, 'score' => 1500])['subtitle'])
        ->toBe('1st · 1,500 pts');
});

it('shows only what it knows about a completed quest', function () {
    expect(activityFor('quest_completed', ['score' => 300])['subtitle'])->toBe('300 pts');
});

it('writes the ordinal for a placement correctly', function (int $placement, string $expected) {
    expect(activityFor('quest_completed', ['placement' => $placement])['subtitle'])->toBe($expected);
})->with([
    'first' => [1, '1st'],
    'second' => [2, '2nd'],
    'third' => [3, '3rd'],
    'fourth' => [4, '4th'],
    'eleventh' => [11, '11th'],
    'twelfth' => [12, '12th'],
    'thirteenth' => [13, '13th'],
    'twenty-first' => [21, '21st'],
    'hundred and eleventh' => [111, '111th'],
]);

it('shows the stars given when a quest was rated', function () {
    expect(activityFor('quest_rated', ['rating' => 4])['subtitle'])
        ->toBe('Copenhagen History Hunt · 4★');
});

it('treats a rating it was not told about as none', function () {
    expect(activityFor('quest_rated')['subtitle'])->toBe('Copenhagen History Hunt · 0★');
});

it('carries the icon for the activity type', function () {
    expect(activityFor('quest_created')['icon'])->not->toBeEmpty();
});
