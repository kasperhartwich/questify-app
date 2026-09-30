<?php

/**
 * The join site moved from join.questifyapp.net to join.getquestify.app. QR
 * codes on paper and links in old chats still name the old host, so it has to
 * keep leading players to the same screen on the new one.
 */
beforeEach(fn () => mockFullApiClient());

it('points the join site at join.getquestify.app by default', function () {
    $config = require config_path('questify.php');

    expect($config['join']['url'])->toBe(env('QUESTIFY_JOIN_URL', 'https://join.getquestify.app'))
        ->and(config('questify.join.legacy_hosts'))->toContain('join.questifyapp.net');
});

it('sends a shared join link on the old host to the same screen on the new one', function () {
    $this->get('https://join.questifyapp.net/join/XK92PL/name?ref=qr')
        ->assertStatus(301)
        ->assertRedirect(config('questify.join.url').'/join/XK92PL/name?ref=qr');
});

it('sends the old host root to the new site', function () {
    $this->get('https://join.questifyapp.net/')
        ->assertStatus(301)
        ->assertRedirect(config('questify.join.url').'/');
});

it('matches the old host regardless of case', function () {
    $this->get('https://JOIN.QuestifyApp.net/join')
        ->assertStatus(301)
        ->assertRedirect(config('questify.join.url').'/join');
});

it('does not redirect a form post on the old host, which would lose its body', function () {
    $response = $this->post('https://join.questifyapp.net/locale/da');

    expect($response->getStatusCode())->not->toBe(301);
});

it('serves the new host without a redirect', function () {
    $this->get('https://join.getquestify.app/join')->assertOk();
});

it('leaves the packaged app alone, whose screens come from 127.0.0.1', function () {
    $this->get('http://127.0.0.1/join')->assertOk();
});
