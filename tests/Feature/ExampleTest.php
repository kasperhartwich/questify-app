<?php

use App\Models\User;

test('the welcome page renders for guests', function () {
    $this->get('/')->assertOk();
});

test('the welcome page hands authenticated users to discover', function () {
    // Stopping on the marketing pitch read as "signed out" at every cold start.
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/discover/list');
});
