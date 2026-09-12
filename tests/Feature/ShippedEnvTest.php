<?php

/**
 * The packager copies the developer's .env into the bundle, so a local
 * APP_DEBUG=true shipped to TestFlight and players saw full exception pages
 * with stack traces. Stripping both keys makes the app fall back to Laravel's
 * defaults: production, debug off.
 */
it('strips the environment and debug flags from packaged builds', function () {
    $stripped = config('nativephp.cleanup_env_keys');

    expect($stripped)->toContain('APP_DEBUG')
        ->and($stripped)->toContain('APP_ENV');
});

it('defaults to production with debug off when the keys are absent', function () {
    expect(env('APP_DEBUG', false))->toBeFalsy()
        ->and(config('app.env', 'production'))->not->toBeEmpty();

    // Laravel's own defaults, which the bundle now relies on.
    $appConfig = require config_path('app.php');

    expect($appConfig['debug'])->toBeFalsy();
})->skip(fn () => env('APP_DEBUG') === true, 'Local .env has debug on, which is expected during development');

/**
 * The app shipped for months with sentry-laravel installed but no DSN, so every
 * PHP exception — including the API validation failures HandlesApiErrors
 * deliberately reports — went nowhere. Only the JS loader script was wired up.
 */
it('has a crash reporting dsn so php exceptions reach sentry', function () {
    // The test run itself has the DSN blanked in phpunit.xml, so assert against
    // the .env the packager actually copies into the bundle.
    $env = file_get_contents(base_path('.env'));

    expect($env)->toMatch('/^SENTRY_LARAVEL_DSN=https:\/\/\S+/m');
})->skip(fn () => ! file_exists(base_path('.env')), 'No .env in this checkout (CI)');

it('keeps the crash reporting dsn out of the stripped key list', function () {
    $patterns = config('nativephp.cleanup_env_keys');

    $matches = collect($patterns)->contains(
        fn (string $pattern) => fnmatch($pattern, 'SENTRY_LARAVEL_DSN')
    );

    expect($matches)->toBeFalse('The DSN must survive packaging or shipped builds report nothing.');
});
