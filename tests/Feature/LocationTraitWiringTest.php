<?php

use Illuminate\Support\Facades\File;

/**
 * Importing the trait is not the same as using it. The wizard imported
 * RequestsLocation but never applied it, so tapping the map's locate button
 * blew up with "Public method [requestLocation] not found on component".
 */
it('applies RequestsLocation wherever it is imported', function () {
    $broken = [];

    foreach (File::allFiles(resource_path('views/pages')) as $file) {
        $contents = $file->getContents();

        if (! str_contains($contents, 'use App\Livewire\Concerns\RequestsLocation;')) {
            continue;
        }

        if (! preg_match('/^\s+use [^;]*\bRequestsLocation\b[^;]*;/m', $contents)) {
            $broken[] = $file->getRelativePathname();
        }
    }

    expect($broken)->toBe([], 'Imported but never applied: '.implode(', ', $broken));
});

it('exposes requestLocation on every screen that offers a locate button', function (string $component) {
    expect(method_exists(app('livewire')->new($component), 'requestLocation'))->toBeTrue();
})->with([
    'pages::discover.quest-map',
    'pages::discover.quest-list',
    'pages::create.quest-wizard',
    'pages::profile.settings',
]);
