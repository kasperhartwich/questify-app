<?php

/*
 * Polyfills for functions missing from the NativePHP mobile PHP runtime.
 *
 * The Android runtime ships without gethostname(), which sentry-laravel calls
 * unconditionally while building its default options — crashing every request
 * on-device. Namespaced calls like Sentry\gethostname() fall back to this
 * global definition.
 */

if (! function_exists('gethostname')) {
    function gethostname(): string|false
    {
        return 'questify-mobile';
    }
}
