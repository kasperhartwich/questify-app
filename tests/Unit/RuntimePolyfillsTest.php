<?php

it('loads runtime polyfills so gethostname always exists', function () {
    // The NativePHP Android runtime lacks gethostname(); the polyfill in
    // app/Support/runtime-polyfills.php guarantees it (Sentry calls it at boot).
    expect(function_exists('gethostname'))->toBeTrue();
    expect(composer_polyfill_file_is_autoloaded())->toBeTrue();
});

function composer_polyfill_file_is_autoloaded(): bool
{
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    return in_array('app/Support/runtime-polyfills.php', $composer['autoload']['files'] ?? [], true);
}
