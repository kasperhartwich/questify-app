<?php

/**
 * Apple rejects generic purpose strings (guideline 5.1.1). The geolocation and
 * scanner plugins ship defaults like "This app needs access to your location";
 * these overrides are keyed by the real Info.plist name so the build applies
 * them after the plugin defaults.
 */
it('overrides the plugin default permission purpose strings', function (string $key) {
    $value = config("nativephp.permissions.$key");

    expect($value)->toBeString()
        ->and($value)->toContain('Questify')
        ->and(strlen($value))->toBeGreaterThan(40);
})->with([
    'NSLocationWhenInUseUsageDescription',
    'NSLocationAlwaysAndWhenInUseUsageDescription',
    'NSCameraUsageDescription',
]);

it('keeps the permission feature toggles boolean', function () {
    expect(config('nativephp.permissions.location'))->toBeTrue()
        ->and(config('nativephp.permissions.camera'))->toBeTrue();
});
