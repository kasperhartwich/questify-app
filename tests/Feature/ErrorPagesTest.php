<?php

use Illuminate\Support\Facades\Log;

it('shows a branded not-found page without HTTP jargon', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound()
        ->assertSee(__('errors.not_found_title'))
        ->assertSee(__('errors.not_found_body'))
        ->assertDontSee('404')
        ->assertDontSee('Not Found');
});

it('keeps the tab bar and a way back on the error page', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertSee(__('general.back'))
        ->assertSee('app-tab-bar', false);
});

it('reports a missing route instead of silently ignoring it', function () {
    // Laravel ignores every HttpException, so a dead link we shipped would
    // otherwise never reach the log or Sentry.
    Log::spy();

    $this->get('/another-missing-route')->assertNotFound();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context = []) => $message === 'Route not found'
            && str_contains($context['url'] ?? '', 'another-missing-route'))
        ->once();
});
