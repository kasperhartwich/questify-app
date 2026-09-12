<?php

use App\Models\User;

beforeEach(fn () => mockFullApiClient());

// --- Public Pages ---

it('renders welcome page for guests', function () {
    $this->get('/')->assertOk();
});

it('renders welcome page for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

it('renders login page', function () {
    $this->get('/login')->assertOk();
});

it('renders register page', function () {
    $this->get('/register')->assertOk();
});

it('renders quest list page with quests', function () {
    $this->actingAs(User::factory()->create())
        ->get('/discover/list')
        ->assertOk()
        ->assertSee('Copenhagen History Hunt');
});

it('renders quest list with category filters', function () {
    $this->actingAs(User::factory()->create())
        ->get('/discover/list')
        ->assertOk()
        ->assertSee('History')
        ->assertSee('General Knowledge');
});

it('renders quest detail page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/quests/1')
        ->assertOk()
        ->assertSee('Copenhagen History Hunt');
});

it('renders quest map page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/discover/map')->assertOk();
});

// --- Guest Redirects ---

it('redirects guest from create page', function () {
    $this->get('/create')->assertRedirect('/');
});

it('redirects guest from profile page', function () {
    $this->get('/profile')->assertRedirect('/');
});

it('redirects guest from my-quests page', function () {
    $this->get('/my-quests')->assertRedirect('/');
});

it('redirects guest from created-quests page', function () {
    $this->get('/my-quests/created')->assertRedirect('/');
});

// --- Authenticated Pages ---

it('renders profile page', function () {
    $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
});

it('renders my played quests page', function () {
    $this->actingAs(User::factory()->create())->get('/my-quests')->assertOk();
});

it('renders my created quests page', function () {
    $this->actingAs(User::factory()->create())->get('/my-quests/created')->assertOk();
});

it('renders create quest page', function () {
    $this->actingAs(User::factory()->create())->get('/create')->assertOk();
});

// Session pages require deeper Blade template updates and are tested via the API tests

it('shows the category alongside the difficulty on a quest card', function () {
    $this->actingAs(User::factory()->create())
        ->get('/discover/list')
        ->assertOk()
        ->assertSee(__('general.medium'))
        ->assertSee('History');
});

it('shows quest meta instead of a duplicate status on created quests', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get('/my-quests/created')
        ->assertOk();

    // The badge already carries the status; the line under the title now
    // shows the quest's own description, and editing lives on the quest page.
    $response->assertSee('Explore the historical heart of Copenhagen!', false)
        ->assertSee(__('general.medium'))
        ->assertSee('History')
        ->assertDontSeeHtml('href="/create/1"');
});
