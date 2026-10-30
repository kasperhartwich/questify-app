<?php

namespace App\Services;

use App\Contracts\ReadsAppBuild;

/**
 * Asks the phone through the `Questify.AppBuild` bridge function of the
 * questify/distribution plugin. The packager raises the build number after
 * the config was baked, so the number in the config can be one behind.
 */
class NativeAppBuildReader implements ReadsAppBuild
{
    public function read(): ?int
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        try {
            $result = json_decode((string) nativephp_call('Questify.AppBuild', '{}'), true);
        } catch (\Throwable) {
            return null;
        }

        $value = $result['build'] ?? $result['data']['build'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
