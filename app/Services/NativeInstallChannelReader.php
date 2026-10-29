<?php

namespace App\Services;

use App\Contracts\ReadsInstallChannel;

/**
 * Asks the phone through the `Questify.InstallChannel` bridge function of the
 * questify/distribution plugin, which reads the name of the App Store
 * receipt: TestFlight's is called `sandboxReceipt`.
 */
class NativeInstallChannelReader implements ReadsInstallChannel
{
    public function read(): ?string
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        try {
            $result = json_decode((string) nativephp_call('Questify.InstallChannel', '{}'), true);
        } catch (\Throwable) {
            return null;
        }

        $value = $result['channel'] ?? $result['data']['channel'] ?? null;

        return in_array($value, [InstallChannel::TESTFLIGHT, InstallChannel::APP_STORE, InstallChannel::DEBUG], true) ? $value : null;
    }
}
