<?php

/**
 * A join link on join.getquestify.app opens the Questify app on Android when
 * it is installed. Android only verifies the app's https intent filter
 * (written by NativePHP from nativephp.deeplink_host) once it has fetched the
 * site's assetlinks.json: a 200 with application/json, no redirect, naming
 * the package and the fingerprints of the certificates it is signed with.
 */
const UPLOAD_KEY = '14:6D:E9:83:C5:73:06:50:D8:EE:B9:95:2F:34:FC:64:16:A0:83:42:E6:1D:BE:A8:8A:04:96:B2:3F:CF:44:E5';
const PLAY_KEY = 'FA:C6:17:45:DC:09:03:78:6F:B9:ED:E6:2A:96:2B:39:9F:73:48:F0:BB:6F:89:9B:83:32:66:75:91:03:3B:9C';

function assetLinks(): array
{
    return test()->get('https://join.getquestify.app/.well-known/assetlinks.json')->json();
}

beforeEach(function () {
    config(['questify.app_links.sha256_cert_fingerprints' => [PLAY_KEY, UPLOAD_KEY]]);
});

it('serves the asset links file as json without a redirect', function () {
    $response = $this->get('https://join.getquestify.app/.well-known/assetlinks.json');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json');

    expect($response->headers->has('Location'))->toBeFalse();
});

it('does not start a session or set cookies for the file', function () {
    $response = $this->get('https://join.getquestify.app/.well-known/assetlinks.json');

    expect($response->headers->getCookies())->toBeEmpty();
});

it('lets the questify app handle the links, signed with the configured keys', function () {
    expect(assetLinks())->toBe([
        [
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => 'com.focusweb.questify',
                'sha256_cert_fingerprints' => [PLAY_KEY, UPLOAD_KEY],
            ],
        ],
    ]);
});

it('names the same app as the ios file', function () {
    $iosBundleId = str(config('questify.universal_links.app_ids')[0])->after('.')->toString();

    expect(assetLinks()[0]['target']['package_name'])->toBe($iosBundleId);
});

it('is not found, without cookies, while no fingerprint is configured', function () {
    config(['questify.app_links.sha256_cert_fingerprints' => []]);

    $response = $this->get('https://join.getquestify.app/.well-known/assetlinks.json');

    $response->assertNotFound();

    expect($response->headers->getCookies())->toBeEmpty();
});

it('reads comma separated fingerprints from the environment', function () {
    $key = 'QUESTIFY_ANDROID_CERT_SHA256';
    $original = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
    $value = ' '.strtolower(PLAY_KEY).' , '.UPLOAD_KEY.',';
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv("{$key}={$value}");

    try {
        $config = require config_path('questify.php');
    } finally {
        [$env, $server, $process] = $original;
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
        if ($env !== null) {
            $_ENV[$key] = $env;
        }
        if ($server !== null) {
            $_SERVER[$key] = $server;
        }
        if ($process !== false) {
            putenv("{$key}={$process}");
        }
    }

    expect($config['app_links']['sha256_cert_fingerprints'])->toBe([PLAY_KEY, UPLOAD_KEY]);
});
