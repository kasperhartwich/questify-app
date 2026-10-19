<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Serves the digital asset links file on the join site, so a join link opens
 * the Questify app on Android when it is installed (app links). Android
 * verifies the app's https intent filter for nativephp.deeplink_host against
 * this file: a 200 with application/json and no redirect, naming the package
 * and its signing certificate fingerprints. Until a fingerprint is configured
 * the file is a 404 and the links open in the browser.
 */
class AndroidAssetLinksController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $fingerprints = config('questify.app_links.sha256_cert_fingerprints');

        abort_if($fingerprints === [], 404);

        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('questify.app_links.package_name'),
                    'sha256_cert_fingerprints' => $fingerprints,
                ],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
