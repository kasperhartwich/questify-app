<?php

use Illuminate\Support\Facades\File;

it('keeps emoji out of the views and the strings', function () {
    $files = collect(File::allFiles(resource_path('views')))
        ->reject(fn ($file) => str_contains($file->getPathname(), 'views/scribe/'))
        ->merge(File::allFiles(lang_path()));

    foreach ($files as $file) {
        expect(preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{2604}\x{2607}-\x{27BF}\x{2B50}\x{FE0F}]/u', File::get($file->getPathname())))
            ->toBe(0, $file->getRelativePathname().' holds an emoji, use an icon');
    }
});
