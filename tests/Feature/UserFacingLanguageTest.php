<?php

use Illuminate\Support\Facades\File;

/**
 * Questify is an app, not a website: nothing a player reads may expose the web
 * stack underneath. See the "User-Facing Language" rules in CLAUDE.md.
 */
$forbidden = ['page', 'link', 'URL', 'browser', 'website', 'http'];

it('keeps web wording out of the error screens', function () use ($forbidden) {
    foreach (['en', 'da'] as $locale) {
        $strings = require lang_path("$locale/errors.php");

        foreach ($strings as $key => $value) {
            if (! is_string($value) || ! str_contains($key, '_title') && ! str_contains($key, '_body')) {
                continue;
            }

            foreach ($forbidden as $word) {
                expect(mb_strtolower($value))->not->toContain(mb_strtolower($word),
                    "errors.$key ($locale) uses web wording: \"$value\"");
            }
        }
    }
});

it('never shows an HTTP status code on an error screen', function () {
    foreach (File::glob(resource_path('views/errors/*.blade.php')) as $view) {
        $contents = File::get($view);

        expect($contents)->not->toMatch('/>\s*(404|403|419|429|500|503)\s*</')
            ->and($contents)->not->toContain('Not Found')
            ->and($contents)->not->toContain('Server Error');
    }
});
