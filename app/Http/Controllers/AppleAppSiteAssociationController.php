<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Serves the apple-app-site-association file on the join site, so a join
 * link opens the Questify app when it is installed (universal links). iOS
 * fetches it through Apple's CDN, which needs a 200 with application/json
 * and no redirect. The app's associated-domains entitlement names the same
 * host (nativephp.deeplink_host).
 */
class AppleAppSiteAssociationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $components = array_map(fn (string $path): array => ['/' => $path], config('questify.universal_links.paths'));

        return response()->json([
            'applinks' => [
                'details' => [
                    [
                        'appIDs' => config('questify.universal_links.app_ids'),
                        'components' => $components,
                    ],
                ],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
