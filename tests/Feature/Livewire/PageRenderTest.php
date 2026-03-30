<?php

use App\Models\Quest;
use App\Models\User;

it('renders welcome page for guests', function () {
    $this->get('/')->assertOk();
});

it('renders quest list page', function () {
    $this->get('/discover/list')->assertOk();
});

it('renders quest detail page', function () {
    $quest = Quest::factory()->create();

    $this->get("/quests/{$quest->id}")->assertOk();
});

it('renders profile page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

it('renders create quest page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/create')->assertOk();
});

it('renders my quests page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/my-quests')->assertOk();
});

it('renders my created quests page for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/my-quests/created')->assertOk();
});
