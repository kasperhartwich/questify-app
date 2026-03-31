<?php

it('redirects my-quests to login when not authenticated', function () {
    $this->get('/my-quests')->assertRedirect('/login');
});

it('redirects profile to login when not authenticated', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('renders the discover page', function () {
    $this->get('/discover/list')->assertOk();
});

it('discover page shows nearby quests heading', function () {
    $this->get('/discover/list')->assertSee('Nearby Quests');
});
