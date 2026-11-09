<?php

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\Api\Resources\QuestApiResource;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(fn () => mockFullApiClient());

/**
 * @param  array<int, array<string, mixed>>  $quests
 */
function swapNearby(array $quests): void
{
    $mock = Mockery::mock(QuestApiResource::class);
    $mock->shouldReceive('nearby')->andReturn(['data' => $quests]);

    swapApiResource('quests', $mock);
}

function nearbyQuest(int $id, string $title, float $km): array
{
    return ['id' => $id, 'title' => $title, 'distance_to_start_km' => $km, 'estimated_duration_minutes' => 30];
}

function joinScreen(): Testable
{
    return Livewire::actingAs(User::factory()->create())->test('pages::join.index');
}

it('shows the nearest quest big and the next two as simple rows, closest first', function () {
    swapNearby([
        nearbyQuest(3, 'Far Quest', 4.2),
        nearbyQuest(1, 'Close Quest', 0.35),
        nearbyQuest(2, 'Middle Quest', 1.4),
        nearbyQuest(4, 'Fourth Quest', 9.0),
    ]);

    joinScreen()
        ->assertSeeInOrder(['Close Quest', '350 m', 'Open quest', 'Middle Quest', '1.4 km', 'Far Quest', '4.2 km'])
        ->assertDontSee('Fourth Quest')
        ->assertSeeHtml('data-nearest="first"')
        ->assertSeeHtml('href="/quests/1"')
        ->assertSeeHtml('href="/quests/2"')
        ->assertSeeHtml('href="/quests/3"')
        ->assertSee('Join Quest');
});

it('copes with a single quest nearby', function () {
    swapNearby([nearbyQuest(1, 'Lonely Quest', 2.0)]);

    joinScreen()
        ->assertSee('Lonely Quest')
        ->assertSeeHtml('data-nearest="first"')
        ->assertDontSeeHtml('data-nearest="other"');
});

it('says so when no quest is nearby', function () {
    swapNearby([]);

    joinScreen()
        ->assertSeeHtml('data-nearest="none"')
        ->assertSee('No quests near you yet')
        ->assertSee('Join Quest');
});

it('asks for location when the player has turned it off', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);

    joinScreen()
        ->set('locationAsked', true)
        ->call('onLocationPermissionStatus', 'denied')
        ->assertSet('nearestState', 'denied')
        ->assertSeeHtml('data-nearest="denied"')
        ->assertSee('Turn on location')
        ->assertDontSee('Close Quest')
        ->assertSee('Join Quest');
});

it('shows the blocked wording when location is blocked for good', function () {
    joinScreen()
        ->set('locationAsked', true)
        ->call('onLocationPermissionStatus', 'permanently_denied')
        ->assertSee('Location access is blocked for Questify');
});

it('treats a failed position fix as location off', function () {
    joinScreen()
        ->set('awaitingFix', true)
        ->call('onLocationReceived', false, null, null)
        ->assertSet('nearestState', 'denied');
});

it('loads the quests once a fix arrives', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);

    joinScreen()
        ->set('nearestState', 'locating')
        ->assertSeeHtml('data-nearest="locating"')
        ->set('awaitingFix', true)
        ->call('onLocationReceived', true, 55.68, 12.59)
        ->assertSet('nearestState', 'ready')
        ->assertSee('Close Quest');
});

it('leaves the join screen as it was for a guest', function () {
    Livewire::test('pages::join.index')
        ->assertDontSeeHtml('data-nearest-state')
        ->assertSee('Join Quest');
});

it('answers in Danish', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);
    app()->setLocale('da');

    joinScreen()->assertSee('Tættest på dig')->assertSee('Åbn quest');
});

it('stops waiting when no position arrives in time and lets the player retry', function () {
    joinScreen()
        ->set('nearestState', 'locating')
        ->assertSeeHtml('onLocationTimeout()')
        ->call('onLocationTimeout')
        ->assertSet('nearestState', 'timeout')
        ->assertSeeHtml('data-nearest="timeout"')
        ->assertSee('Could not find your position')
        ->set('isNative', true)
        ->call('retryLocation')
        ->assertSet('nearestState', 'locating')
        ->assertSet('locationAsked', true);
});

it('reloads the default spot when Try again is pressed outside the app', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);

    joinScreen()
        ->set('nearestState', 'timeout')
        ->call('retryLocation')
        ->assertSet('nearestState', 'ready')
        ->assertSee('Close Quest');
});

it('does not time out once the quests are shown', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);

    joinScreen()
        ->set('awaitingFix', true)
        ->call('onLocationReceived', true, 55.68, 12.59)
        ->call('onLocationTimeout')
        ->assertSet('nearestState', 'ready');
});

it('uses a fix that arrives after the timeout', function () {
    swapNearby([nearbyQuest(1, 'Close Quest', 0.5)]);

    joinScreen()
        ->set('awaitingFix', true)
        ->call('onLocationTimeout')
        ->call('onLocationReceived', true, 55.68, 12.59)
        ->assertSet('nearestState', 'ready')
        ->assertSee('Close Quest');
});

it('says so when the nearby call fails instead of claiming there are no quests', function () {
    $mock = Mockery::mock(QuestApiResource::class);
    $mock->shouldReceive('nearby')->andThrow(new ApiException(500, 'boom'));
    swapApiResource('quests', $mock);

    joinScreen()
        ->set('awaitingFix', true)
        ->call('onLocationReceived', true, 55.68, 12.59)
        ->assertSet('nearestState', 'failed')
        ->assertSeeHtml('data-nearest="failed"')
        ->assertDontSee('No quests near you yet')
        ->assertDispatched('api-error');
});
