<?php

namespace App\Services;

use Native\Mobile\Facades\Browser;

/**
 * Opens the address of an update. A TestFlight address opens the TestFlight
 * app itself, which a web view of testflight.apple.com does not do for an
 * installed tester.
 */
class StoreLink
{
    public function target(string $url): string
    {
        if (preg_match('~^https://(?:testflight|beta\.itunes)\.apple\.com/v1/app/(\d+)~', $url, $match) === 1) {
            return 'itms-beta://beta.itunes.apple.com/v1/app/'.$match[1];
        }

        return $url;
    }

    public function open(string $url): void
    {
        $target = $this->target($url);

        if (function_exists('nativephp_call')) {
            try {
                $result = json_decode((string) nativephp_call('Questify.OpenUrl', json_encode(['url' => $target])), true);

                if (($result['opened'] ?? $result['data']['opened'] ?? false) === true) {
                    return;
                }
            } catch (\Throwable) {
            }
        }

        Browser::open($url);
    }
}
