<?php

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\Api\Resources\QuestApiResource;
use Livewire\Livewire;

/**
 * The shared trait behind every screen: it shapes API arrays into objects for
 * Blade and owns the bookmark button that appears on every quest card. A
 * regression here is not one broken screen but all of them.
 */
beforeEach(fn () => mockFullApiClient());

function listPage()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-list');
}

it('shapes api arrays so blade can walk them with arrow syntax', function () {
    // json_decode is what turns nested arrays into nested objects; a plain
    // (object) cast would leave the second level as an array and blow up in
    // the template.
    listPage()->assertSee('History');
});

it('toggles a bookmark from a quest card', function () {
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('toggleFavourite')->once()->with(1)->andReturn(['data' => ['is_favourited' => true]]);
    $quests->shouldReceive('nearby')->andReturn(['data' => []]);
    $quests->shouldReceive('list', 'show', 'rate')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    $component = listPage();

    expect($component->call('toggleCardFavourite', 1)->effects['returns'][0] ?? null)
        ->toBe(['is_favourited' => true]);
});

it('signs the player out and asks them to log in again when the token is rejected', function () {
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('toggleFavourite')->andThrow(new ApiAuthenticationException('Unauthenticated.'));
    $quests->shouldReceive('nearby')->andReturn(['data' => []]);
    $quests->shouldReceive('list', 'show', 'rate')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    listPage()
        ->call('toggleCardFavourite', 1)
        ->assertRedirect(route('login'));
});

it('reports an ordinary failure without throwing the player out', function () {
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('toggleFavourite')->andThrow(new ApiException(500, 'Something went wrong'));
    $quests->shouldReceive('nearby')->andReturn(['data' => []]);
    $quests->shouldReceive('list', 'show', 'rate')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    listPage()
        ->call('toggleCardFavourite', 1)
        ->assertDispatched('api-error')
        ->assertNoRedirect();
});

it('skips malformed rows rather than rendering nothing', function () {
    // A single bad row in a list must not take the whole screen down.
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('nearby')->andReturn(['data' => [
        'not-an-array',
        ['id' => 1, 'title' => 'Copenhagen History Hunt', 'difficulty' => 'easy', 'category' => ['name' => 'History']],
    ]]);
    $quests->shouldReceive('list', 'show', 'rate', 'toggleFavourite')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    listPage()->assertSee('Copenhagen History Hunt');
});
