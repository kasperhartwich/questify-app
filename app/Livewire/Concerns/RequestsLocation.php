<?php

namespace App\Livewire\Concerns;

use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Geolocation\PermissionRequestResult;
use Native\Mobile\Events\Geolocation\PermissionStatusReceived;
use Native\Mobile\Facades\Geolocation;

/**
 * Asks for a GPS fix, prompting for permission first when the user has not
 * decided yet.
 *
 * `Geolocation::getCurrentPosition()` never surfaces the OS permission dialog
 * on its own — with an undetermined permission it simply returns nothing, so
 * buttons appear dead. Always call `requestLocation()` instead: it checks the
 * current status, shows the system prompt when the user has not been asked,
 * and only then reads the position.
 *
 * The flow is event-driven:
 *   requestLocation() → checkPermissions()
 *     → PermissionStatusReceived  granted        → position
 *                                 not_determined → requestPermissions()
 *                                 denied         → onLocationPermissionDenied()
 *     → PermissionRequestResult   granted        → position
 *                                 otherwise      → onLocationPermissionDenied()
 */
trait RequestsLocation
{
    public function requestLocation(): void
    {
        try {
            Geolocation::checkPermissions()->get();
        } catch (\Throwable) {
            // Not running on a native device — nothing to ask.
        }
    }

    #[OnNative(PermissionStatusReceived::class)]
    public function onLocationPermissionStatus(string $location = '', string $coarseLocation = '', string $fineLocation = ''): void
    {
        if ($location === 'granted') {
            $this->fetchCurrentPosition();

            return;
        }

        if ($location === 'not_determined') {
            Geolocation::requestPermissions()->get();

            return;
        }

        $this->onLocationPermissionDenied($location);
    }

    #[OnNative(PermissionRequestResult::class)]
    public function onLocationPermissionRequestResult(string $location = '', string $coarseLocation = '', string $fineLocation = '', ?string $error = null): void
    {
        if ($location === 'granted') {
            $this->fetchCurrentPosition();

            return;
        }

        $this->onLocationPermissionDenied($location);
    }

    /**
     * Whether this screen needs GPS-grade accuracy. Navigation screens override
     * this; browsing screens are fine with a network fix.
     */
    protected function wantsFineLocation(): bool
    {
        return false;
    }

    protected function fetchCurrentPosition(): void
    {
        Geolocation::getCurrentPosition($this->wantsFineLocation());
    }

    /**
     * Tell the user why nothing happened. Screens may override for a different
     * message or a settings deep link.
     */
    protected function onLocationPermissionDenied(string $status): void
    {
        $this->dispatch('api-error', message: $status === 'permanently_denied'
            ? __('general.location_permission_blocked')
            : __('general.location_permission_denied'));
    }
}
