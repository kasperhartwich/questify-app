<?php

use App\Models\User;

/**
 * The wizard's "Change" language link pointed at /settings, which is not a
 * route — tapping it produced a 404 instead of opening the language settings.
 */
it('links the wizard language row to a page that exists', function () {
    mockFullApiClient();

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/create')->assertOk()->getContent();

    preg_match('/href="([^"]*)"[^>]*>\s*'.preg_quote(__('general.change'), '/').'/', $html, $m);
    $target = $m[1] ?? null;

    expect($target)->not->toBeNull('The language row has no Change link at all.');

    $this->actingAs($user)->get($target)->assertOk();
});
