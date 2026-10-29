<?php

namespace Questify\Distribution;

use Illuminate\Support\ServiceProvider;

/**
 * Carries only the native side: the `Questify.InstallChannel` bridge function
 * declared in nativephp.json. The app reads it through
 * `App\Services\NativeInstallChannelReader`.
 */
class DistributionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
}
