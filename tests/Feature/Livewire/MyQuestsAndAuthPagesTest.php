<?php

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Models\User;
use App\Services\Api\Resources\AuthResource;
use App\Services\Api\Resources\QuestApiResource;
use App\Services\Api\Resources\UserApiResource;
use Livewire\Livewire;

beforeEach(fn () => mockFullApiClient());

// --- My Quests ---

function myQuests()
{
    return Livewire::actingAs(User::factory()->create())
        ->test('pages::my-quests.played-quests');
}

it('opens on the favourites tab', function () {
    myQuests()->assertSet('tab', 'favourites');
});

it('loads only the data the open tab needs', function () {
    // Each tab hits a different endpoint; loading all three on every switch
    // would triple the requests on a screen players flick between.
    myQuests()
        ->assertSet('createdQuestsData', [])
        ->assertSet('participationsData', []);
});

it('loads the created quests when that tab is opened', function () {
    $component = myQuests()->set('tab', 'created');

    expect($component->get('createdQuestsData'))->toHaveCount(1);
    expect($component->get('createdQuestsData')[0]['title'])->toBe('Copenhagen History Hunt');
});

it('loads participations for the playing and history tabs', function (string $tab) {
    myQuests()->set('tab', $tab)->assertSet('createdQuestsData', []);
})->with(['playing', 'history']);

it('forgets the page position when the tab changes', function () {
    // Carrying a cursor across tabs would page one list by another's offset.
    myQuests()
        ->set('cursor', 'some-cursor')
        ->set('tab', 'created')
        ->assertSet('cursor', '');
});

it('reloads the list after archiving a quest', function () {
    $quests = Mockery::mock(QuestApiResource::class);
    $quests->shouldReceive('destroy')->once()->with(1)->andReturn(['data' => []]);
    $quests->shouldReceive('list', 'show', 'nearby', 'rate')->andReturn(['data' => []]);
    swapApiResource('quests', $quests);

    myQuests()->set('tab', 'created')->call('archiveQuest', 1);
});

it('survives a favourites request the backend refuses', function () {
    // A quest bookmarked then made private used to 403 the whole screen.
    $user = Mockery::mock(UserApiResource::class);
    $user->shouldReceive('favourites')->andThrow(new ApiException(403, 'Forbidden'));
    $user->shouldReceive('quests')->andReturn(['data' => [], 'meta' => ['next_cursor' => null]]);
    $user->shouldReceive('sessions')->andReturn(['data' => []]);
    swapApiResource('user', $user);

    myQuests()->assertSet('favouriteQuestsData', []);
});

it('renders the created quests screen', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::my-quests.created-quests')
        ->assertSee('Copenhagen History Hunt');
});

// --- Forgot password ---

it('requires an email address', function () {
    Livewire::test('pages::auth.forgot-password')
        ->call('sendResetLink')
        ->assertHasErrors('email');
});

it('rejects something that is not an email address', function () {
    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'not-an-email')
        ->call('sendResetLink')
        ->assertHasErrors('email');
});

it('confirms once the reset has been sent', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('forgotPassword')->once()->with('kasper@example.com')->andReturn(['data' => []]);
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'kasper@example.com')
        ->call('sendResetLink')
        ->assertSet('linkSent', true);
});

it('shows the backend\'s own complaint against the field it belongs to', function () {
    $auth = Mockery::mock(AuthResource::class);
    $auth->shouldReceive('forgotPassword')->andThrow(
        new ApiValidationException('Validation failed.', ['email' => ['We do not know that address.']])
    );
    $auth->shouldReceive('me')->andReturn(['data' => ['id' => 1, 'name' => 'Test', 'locale' => 'en']]);
    swapApiResource('auth', $auth);

    Livewire::test('pages::auth.forgot-password')
        ->set('email', 'nobody@example.com')
        ->call('sendResetLink')
        ->assertHasErrors('email')
        ->assertSet('linkSent', false);
});

// --- Welcome ---

it('renders the welcome screen for a visitor', function () {
    Livewire::test('pages::welcome.index')->assertOk();
});

it('offers both ways in from the welcome screen', function () {
    Livewire::test('pages::welcome.index')
        ->assertSee(__('general.login'))
        ->assertSee(__('general.join_quest'));
});

/**
 * The app opens on the welcome screen at every cold start. Until it sent
 * signed-in players onward, every restart looked like being signed out —
 * the token was fine, but the player was staring at the marketing pitch
 * with a Log In button.
 */
it('sends a signed-in player from the welcome screen into the app', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::welcome.index')
        ->assertRedirect('/discover/list');
});

it('keeps the welcome screen for guests', function () {
    Livewire::test('pages::welcome.index')->assertNoRedirect();
});

it('sends a signed-in player away from the login form', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::auth.login')
        ->assertRedirect('/discover/list');
});
