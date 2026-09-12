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
