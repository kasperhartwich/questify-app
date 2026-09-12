<?php

use App\Auth\ApiTokenUser;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => mockFullApiClient());

it('hides the edit tab from someone else\'s quest', function () {
    // The fixture quest belongs to user 2.
    Livewire::actingAs(User::factory()->create())
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertDontSeeHtml("activeTab = 'edit'");
});

it('shows the creator an edit tab with the status and an edit link', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    // The fixture's creator is user 2.
    $owner = new ApiTokenUser(['id' => 2, 'name' => 'Bent Hansen', 'email' => 'b@example.com', 'locale' => 'en']);

    Livewire::actingAs($owner)
        ->test('pages::discover.quest-detail', ['quest' => 7])
        ->assertSeeHtml("activeTab = 'edit'")
        ->assertSee(__('quests.status_draft'))
        ->assertSeeHtml('href="/create/7"');
});

it('tells the creator a published quest cannot be edited', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    $owner = new ApiTokenUser(['id' => 2, 'name' => 'Bent Hansen', 'email' => 'b@example.com', 'locale' => 'en']);

    Livewire::actingAs($owner)
        ->test('pages::discover.quest-detail', ['quest' => 1])
        ->assertSee(__('quests.published_not_editable'))
        ->assertDontSeeHtml('href="/create/1"');
});
