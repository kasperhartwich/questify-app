<?php

use Illuminate\Support\Facades\File;

/**
 * A double quote inside a double-quoted Alpine attribute ends the attribute
 * early. The browser then reads the rest of the JavaScript as more attributes
 * until the first ">" — which arrow functions provide — and prints everything
 * after that as page text. It happened twice: a comment quoting an IP address
 * dumped the whole checkpoint component on top of the map.
 *
 * Nothing in the browser reports this as a template bug: Alpine throws
 * "Unexpected end of script" and "Can't find variable: <whatever survived>",
 * which say nothing about quoting. So guard it at the source instead — a
 * truncated attribute always leaves its braces unbalanced.
 */
function alpineAttributeValues(string $contents): array
{
    $found = [];

    foreach (['x-data', 'x-init', 'x-show', 'x-on:click', '@click'] as $attribute) {
        $offset = 0;
        $needle = $attribute.'="';

        while (($start = strpos($contents, $needle, $offset)) !== false) {
            $valueStart = $start + strlen($needle);
            $end = strpos($contents, '"', $valueStart);
            $offset = $end === false ? $valueStart : $end + 1;

            if ($end === false) {
                continue;
            }

            $found[] = [$attribute, substr($contents, $valueStart, $end - $valueStart)];
        }
    }

    return $found;
}

function isBalanced(string $value): bool
{
    $depth = ['{' => 0, '(' => 0, '[' => 0];
    $close = ['}' => '{', ')' => '(', ']' => '['];

    foreach (str_split($value) as $char) {
        if (isset($depth[$char])) {
            $depth[$char]++;
        } elseif (isset($close[$char])) {
            $depth[$close[$char]]--;
        }
    }

    return $depth['{'] === 0 && $depth['('] === 0 && $depth['['] === 0;
}

it('closes every alpine attribute where its javascript ends', function () {
    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = File::get($file->getPathname());

        foreach (alpineAttributeValues($contents) as [$attribute, $value]) {
            if (! isBalanced($value)) {
                $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname())." ({$attribute})";
            }
        }
    }

    expect($offenders)->toBe([],
        'These Alpine attributes end mid-expression, which means a double quote inside them '
        .'closed the attribute early — the rest of the JavaScript renders as visible text on '
        .'the screen. Use single quotes inside the attribute: '.implode(', ', $offenders)
    );
});

it('checks a meaningful number of views', function () {
    // A silent zero would make the guard above pass forever.
    $withAlpine = collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file) => str_contains(File::get($file->getPathname()), 'x-data="'))
        ->count();

    expect($withAlpine)->toBeGreaterThan(5);
});

it('catches a truncated attribute', function () {
    // The exact shape of the bug: a comment quoting something in double
    // quotes, which ends the attribute at the first one.
    $broken = 'x-data="{ locateUser() { /* prompted by "127.0.0.1" */ } }"';

    [[, $value]] = alpineAttributeValues($broken);

    expect(isBalanced($value))->toBeFalse();
});

it('accepts an attribute that only uses single quotes inside', function () {
    $fine = "x-data=\"{ label: 'Tap map to add checkpoint', open: false }\"";

    [[, $value]] = alpineAttributeValues($fine);

    expect(isBalanced($value))->toBeTrue();
});

/**
 * The static guard above only sees our own templates. The canary in app.js is
 * the net for anything that still reaches a player — it turns Alpine's
 * "Can't find variable: mapExpanded" into a Sentry issue that names the
 * actual problem. Assert it is wired up; behaviour lives in the browser.
 */
it('ships the leaked-markup canary to sentry', function () {
    $appJs = File::get(resource_path('js/app.js'));

    expect($appJs)->toContain('Template markup leaked as visible text')
        ->and($appJs)->toContain('alpine:initialized')
        ->and($appJs)->toContain('livewire:navigated');
});

it('keeps the canary out of the compiled bundle by accident', function () {
    // A build that dropped the guard would leave us blind again.
    $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
    $entry = $manifest['resources/js/app.js'] ?? null;

    expect($entry)->not->toBeNull('app.js is missing from the Vite manifest');
    expect(File::get(public_path('build/'.$entry['file'])))
        ->toContain('Template markup leaked as visible text');
})->skip(fn () => ! file_exists(public_path('build/manifest.json')), 'No build present');
