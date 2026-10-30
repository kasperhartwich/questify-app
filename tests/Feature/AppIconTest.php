<?php

it('ships the app icon NativePHP installs from public/icon.png', function () {
    $path = public_path('icon.png');

    expect(file_exists($path))->toBeTrue();

    [$width, $height, $type] = getimagesize($path);

    expect($type)->toBe(IMAGETYPE_PNG)
        ->and($width)->toBe(1024)
        ->and($height)->toBe(1024);
});

it('has no alpha channel, which the App Store rejects', function () {
    $colorType = ord(file_get_contents(public_path('icon.png'), false, null, 25, 1));

    expect($colorType)->not->toBe(4)->not->toBe(6);
});
