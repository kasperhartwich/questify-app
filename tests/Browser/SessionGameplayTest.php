<?php

use App\Models\User;

// 'XYZ789' resolves to an active in-play session in mockFullApiClient(); the
// actingAs factory user (id 1) matches participant 55 in that session.

beforeEach(fn () => mockFullApiClient());

it('renders the active quest navigation screen', function () {
    $this->actingAs(User::factory()->create());

    visit('/session/XYZ789/play')
        ->assertSee('Nyhavn')
        ->assertNoJavaScriptErrors();
});

it('answers a checkpoint question correctly and sees the feedback', function () {
    $this->actingAs(User::factory()->create());

    visit('/session/XYZ789/question/1')
        ->assertSee('In which year was Nyhavn completed?')
        ->click('button:has-text("1673")')
        ->wait(1)
        ->click('button:has-text("Submit Answer")')
        ->assertSee('Correct!')
        ->assertSee('+100')
        ->assertNoJavaScriptErrors();
});

it('renders the quest complete screen with the leaderboard', function () {
    $this->actingAs(User::factory()->create());

    visit('/session/XYZ789/complete')
        ->assertSee('Kasper Test')
        ->assertSee('100')
        ->assertNoJavaScriptErrors();
});
