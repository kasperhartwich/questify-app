<?php

use App\Models\User;

beforeEach(fn () => mockFullApiClient());

// NOTE: the browser test server runs inside the test process. Actions (fill/click)
// block PHP while Playwright waits for the target element, so an element that only
// appears after a Livewire roundtrip deadlocks unless an awaitable assertion
// (assertVisible/assertSee/assertPathIs — these pump the event loop between
// retries) runs first. Always assert before acting on Livewire-rendered UI.

it('logs in with email and password and lands on discover', function () {
    // The real login flow requires the questify-api guard (phpunit.xml pins
    // AUTH_DRIVER=session so feature tests can use actingAs with factories).
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    visit('/login')
        ->fill('email', 'test@example.com')
        ->assertVisible('input[type="password"]')
        ->fill('input[type="password"]', 'password123')
        ->click('button:has-text("Log In")')
        ->assertPathIs('/discover/list')
        ->assertSee('Copenhagen History Hunt')
        ->assertNoJavaScriptErrors();
});

it('joins a session by code through the display name step to the lobby', function () {
    $this->actingAs(User::factory()->create());

    visit('/join')
        ->fill('input[autocomplete="one-time-code"]', 'ABC123')
        ->wait(1)
        ->assertEnabled('button:has-text("Continue")')
        ->click('button:has-text("Continue")')
        ->assertPathIs('/join/ABC123/name')
        ->assertSee('Copenhagen History Hunt')
        // Type only once Livewire's JS has booted and attached its listener to
        // the deferred wire:model. Filling before that left the field empty at
        // submit time on a cold CI runner, and the screen kept asking for a
        // name. A fixed second is the blunt version of that wait: if this goes
        // flaky again, the cause is here, not in the join code.
        ->wait(1)
        ->fill('input[type="text"]', 'Kasper Test')
        // Awaitable, per the note at the top of this file: it pumps the event
        // loop before the click, which assertValue does not.
        ->assertVisible('button[type="submit"]')
        ->click('button[type="submit"]')
        ->assertPathIs('/session/ABC123')
        ->assertNoJavaScriptErrors();
});

it('registers a new account with email and lands on discover', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    visit('/register')
        ->fill('email', 'anna@example.com')
        ->click('form:has(input[name="email"]) button[type="submit"]')
        ->assertVisible('input[name="first_name"]')
        ->fill('first_name', 'Anna')
        ->fill('display_name', 'AdventureAnna')
        ->fill('input[name="password"]', 'password123')
        ->click('button:has-text("Continue")')
        ->assertPathIs('/discover/list')
        ->assertSee('Copenhagen History Hunt')
        ->assertNoJavaScriptErrors();
});

it('logs in through the social deep link callback', function () {
    config(['auth.guards.web.driver' => 'questify-api']);
    app('auth')->forgetGuards();

    visit('/auth/callback?token=test-token')
        ->assertPathIs('/discover/list')
        ->assertSee('Copenhagen History Hunt')
        ->assertNoJavaScriptErrors();
});

it('navigates from discover list to quest detail', function () {
    $this->actingAs(User::factory()->create());

    visit('/discover/list')
        ->assertSee('Copenhagen History Hunt')
        ->click('Copenhagen History Hunt')
        ->assertPathIs('/quests/1')
        ->assertSee('Explore the historical heart of Copenhagen!')
        ->assertNoJavaScriptErrors();
});

it('starts a quest from the detail page and lands in the play screen', function () {
    $this->actingAs(User::factory()->create());

    visit('/quests/1')
        ->assertSee('Start Quest')
        ->click('button[class*="mb-2.5"][wire\\:click="startQuest"]')
        ->assertPathIs('/session/XYZ789/play')
        ->assertSee('Nyhavn')
        ->assertNoJavaScriptErrors();
});
