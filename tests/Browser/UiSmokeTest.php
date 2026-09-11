<?php

use App\Models\User;

beforeEach(fn () => mockFullApiClient());

// --- Guest pages ---

it('renders guest page without javascript errors', function (string $url) {
    visit($url)->assertNoJavaScriptErrors();
})->with([
    'welcome' => '/',
    'login' => '/login',
    'register' => '/register',
    'join' => '/join',
    'forgot password' => '/forgot-password',
]);

// --- Authenticated pages ---

it('renders authenticated page without javascript errors', function (string $url) {
    $this->actingAs(User::factory()->create());

    visit($url)->assertNoJavaScriptErrors();
})->with([
    'discover list' => '/discover/list',
    'discover map' => '/discover/map',
    'quest detail' => '/quests/1',
    'session lobby' => '/session/ABC123',
    'session host dashboard' => '/session/ABC123/host',
    'my played quests' => '/my-quests',
    'my created quests' => '/my-quests/created',
    'create quest wizard' => '/create',
    'profile' => '/profile',
    'join (logged in)' => '/join',
]);

// --- Key UI flows ---

it('navigates from welcome to login', function () {
    visit('/')
        ->assertSee('Questify')
        ->click('Log In')
        ->assertPathIs('/login')
        ->assertSee('Welcome back')
        ->assertNoJavaScriptErrors();
});

it('navigates from welcome to join quest', function () {
    visit('/')
        ->click('Join Quest')
        ->assertPathIs('/join')
        ->assertSee('Enter session code')
        ->assertNoJavaScriptErrors();
});

it('shows the join hero and disabled continue until a code is entered', function () {
    visit('/join')
        ->assertSee('Join Quest')
        ->assertSee('Continue')
        ->assertNoJavaScriptErrors();
});
