<?php

use App\Models\User;
use App\Services\Api\QuestifyApiClient;
use App\Services\Api\Resources\CategoryApiResource;
use App\Services\Api\Resources\QuestApiResource;
use Livewire\Livewire;

function mockLocationApiClient(): void
{
    $mockQuests = Mockery::mock(QuestApiResource::class);
    $mockQuests->shouldReceive('nearby')->andReturn(['data' => []]);
    $mockQuests->shouldReceive('list')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);

    $mockCategories = Mockery::mock(CategoryApiResource::class);
    $mockCategories->shouldReceive('list')->andReturn(['data' => []]);

    $mockClient = Mockery::mock(QuestifyApiClient::class);
    $mockClient->shouldReceive('quests')->andReturn($mockQuests);
    $mockClient->shouldReceive('categories')->andReturn($mockCategories);
    $mockClient->shouldReceive('get')->with('/info')->andReturn(appInfoStub());

    app()->instance(QuestifyApiClient::class, $mockClient);
}

/**
 * The NativePHP v2 geolocation plugin dispatches LocationReceived with null
 * coordinates when a fix fails (e.g. permission denied, no GPS in simulator).
 * The handler params must accept null or Livewire's argument binding throws a
 * TypeError before the `if (! $success)` guard can run.
 */
it('handles a failed location event with null coordinates on the map page', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-map')
        ->call('onLocationReceived', false, null, null, null, null, null, null)
        ->assertSet('latitude', 55.6761)
        ->assertSet('longitude', 12.5683);
});

it('handles a failed location event with null coordinates on the discover list', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-list')
        ->call('onLocationReceived', false, null, null)
        ->assertOk();
});

it('applies a successful location fix on the map page', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-map')
        ->call('onLocationReceived', true, 40.0, -74.0, 5.0, 0, 'gps', null)
        ->assertSet('latitude', 40.0)
        ->assertSet('longitude', -74.0);
});

/**
 * Geolocation::getCurrentPosition() never surfaces the OS permission dialog on
 * its own — with an undetermined permission the button silently does nothing.
 * requestLocation() must go through checkPermissions() → requestPermissions().
 */
it('asks for permission when the status is undetermined', function (string $component) {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test($component)
        ->call('onLocationPermissionStatus', 'not_determined', 'not_determined', 'not_determined')
        ->assertOk();
})->with([
    'map' => 'pages::discover.quest-map',
    'list' => 'pages::discover.quest-list',
]);

it('warns the user when location permission is denied', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-map')
        ->call('onLocationPermissionStatus', 'denied', 'denied', 'denied')
        ->assertDispatched('api-error');
});

it('warns with the settings hint when permission is permanently denied', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-map')
        ->call('onLocationPermissionRequestResult', 'permanently_denied', 'denied', 'denied')
        ->assertDispatched('api-error', message: __('general.location_permission_blocked'));
});

it('reads the position once permission is granted', function () {
    mockLocationApiClient();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-map')
        ->call('onLocationPermissionRequestResult', 'granted', 'granted', 'granted')
        ->assertNotDispatched('api-error');
});
