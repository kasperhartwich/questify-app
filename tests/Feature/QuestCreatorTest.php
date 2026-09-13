<?php

use App\Auth\ApiTokenUser;
use Livewire\Livewire;

/**
 * The API returns the author under `creator`; it has never returned a `user`
 * key for a quest. The app read `$quest->user`, which resolved to null
 * everywhere — so the byline was blank, cards showed no author, and the
 * creator-only Edit tab never appeared on your own quest. The old fixtures hid
 * it by inventing a `user` key the backend does not send.
 */
beforeEach(function () {
    mockFullApiClient();

    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();
});

function apiUser(int $id): ApiTokenUser
{
    return new ApiTokenUser(['id' => $id, 'name' => 'Viewer', 'locale' => 'en']);
}

it('names the author on the quest detail screen', function () {
    Livewire::actingAs(apiUser(99))
        ->test('pages::discover.quest-detail', ['quest' => 1])
        ->assertSee('Bent Hansen');
});

it('names the author on a quest card in the discover list', function () {
    Livewire::actingAs(apiUser(99))
        ->test('pages::discover.quest-list')
        ->assertSee('Bent Hansen');
});

it('shows the edit tab to the author of the quest', function () {
    // The fixture quest is authored by creator id 2.
    Livewire::actingAs(apiUser(2))
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertSeeHtml("activeTab = 'edit'");
});

it('hides the edit tab from everyone else', function () {
    Livewire::actingAs(apiUser(99))
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertDontSeeHtml("activeTab = 'edit'");
});

/**
 * A contract guard: if the backend ever renames the key again, this fails here
 * rather than silently blanking the author on every screen.
 */
it('reads the author from the key the api actually sends', function () {
    $questResource = file_get_contents(
        dirname(base_path()).'/questify-admin/app/Http/Resources/V1/QuestResource.php'
    );

    expect($questResource)->toContain("'creator' =>")
        ->and($questResource)->not->toContain("'user' => [");
})->skip(
    fn () => ! is_dir(dirname(base_path()).'/questify-admin'),
    'The backend checkout is not available here'
);

/**
 * The badge row showed the visibility setting unconditionally, so a quest still
 * waiting for an admin read "Public" — which is what it will be, not what it
 * is. Until it is published, the badge must say where it actually stands.
 */
it('says awaiting approval on a quest that is still in review', function () {
    // Fixture quest 7 is a draft; quest 1 is published.
    Livewire::actingAs(apiUser(2))
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertDontSee(__('general.public'))
        ->assertSee(__('quests.status_draft'));
});

it('shows the visibility on a published quest', function () {
    Livewire::actingAs(apiUser(99))
        ->test('pages::discover.quest-detail', ['quest' => 1])
        ->assertSee(__('general.public'));
});

it('points the back arrow at my created quests when arriving from the wizard', function () {
    Livewire::actingAs(apiUser(2))
        ->withQueryParams(['from' => 'created'])
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertSeeHtml("'/my-quests/created'");
});

it('leaves the back arrow on history for a normal visit', function () {
    Livewire::actingAs(apiUser(99))
        ->test('pages::discover.quest-detail', ['quest' => 1])
        ->assertSeeHtml('window.history.back()');
});
