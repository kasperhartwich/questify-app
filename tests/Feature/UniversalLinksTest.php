<?php

/**
 * A join link on join.getquestify.app opens the Questify app when it is
 * installed. iOS only trusts the link once it has fetched the site's
 * apple-app-site-association file: a 200 with application/json, no redirect,
 * naming the app and the join paths. The app claims the host with its
 * associated-domains entitlement, which NativePHP writes from
 * nativephp.deeplink_host.
 */
function appleAppSiteAssociation(): array
{
    return test()->get('https://join.getquestify.app/.well-known/apple-app-site-association')->json();
}

it('serves the apple-app-site-association file as json without a redirect', function () {
    $response = $this->get('https://join.getquestify.app/.well-known/apple-app-site-association');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json');

    expect($response->headers->has('Location'))->toBeFalse();
});

it('does not start a session or set cookies for the file', function () {
    $response = $this->get('https://join.getquestify.app/.well-known/apple-app-site-association');

    expect($response->headers->getCookies())->toBeEmpty();
});

it('names the questify app by team and bundle id', function () {
    expect(appleAppSiteAssociation()['applinks']['details'][0]['appIDs'])
        ->toBe(['2KF539NBBF.com.focusweb.questify']);
});

it('claims the join screens and nothing else', function () {
    expect(appleAppSiteAssociation()['applinks']['details'][0]['components'])
        ->toBe([['/' => '/join'], ['/' => '/join/*']]);
});

it('claims the path the lobby shares as its join link', function () {
    $patterns = collect(appleAppSiteAssociation()['applinks']['details'][0]['components'])->pluck('/');
    $sharedPath = parse_url(route('join.name', ['code' => 'XK92PL']), PHP_URL_PATH);

    expect($patterns->contains(fn (string $pattern) => fnmatch($pattern, $sharedPath)))->toBeTrue();
});

it('writes the join host into the app entitlement by default', function () {
    $key = 'NATIVEPHP_DEEPLINK_HOST';
    $original = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
    unset($_ENV[$key], $_SERVER[$key]);
    putenv($key);

    try {
        $config = require config_path('nativephp.php');
    } finally {
        [$env, $server, $process] = $original;
        if ($env !== null) {
            $_ENV[$key] = $env;
        }
        if ($server !== null) {
            $_SERVER[$key] = $server;
        }
        if ($process !== false) {
            putenv("{$key}={$process}");
        }
    }

    expect($config['deeplink_host'])->toBe(parse_url(config('questify.join.url'), PHP_URL_HOST));
});
