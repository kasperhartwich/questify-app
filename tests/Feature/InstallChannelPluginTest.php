<?php

use App\Providers\NativeServiceProvider;
use Questify\Distribution\DistributionServiceProvider;

/**
 * The plugin only does anything when it is registered and declares the bridge
 * function the app asks for: an unregistered plugin fails silently, and the
 * app would call every install an App Store build.
 */
it('compiles the distribution plugin into the native build', function () {
    expect((new NativeServiceProvider(app()))->plugins())->toContain(DistributionServiceProvider::class);
});

it('declares the bridge function the app calls and reads TestFlight from the receipt', function () {
    $manifest = json_decode(file_get_contents(base_path('packages/questify/distribution/nativephp.json')), true);
    $names = array_column($manifest['bridge_functions'], 'name');
    $swift = file_get_contents(base_path('packages/questify/distribution/resources/ios/QuestifyFunctions.swift'));

    expect($names)->toContain('Questify.InstallChannel')
        ->and($swift)->toContain('sandboxReceipt')
        ->and($swift)->toContain('class InstallChannel: BridgeFunction');
});
