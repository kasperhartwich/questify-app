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
    /**
     * Native location events are global: every component on the page hears them,
     * also the ones another component (the centre button) asked for. A screen
     * therefore reacts only to the answers of its own request.
     */
    public bool $locationAsked = false;

    public bool $awaitingFix = false;

    public function requestLocation(): void
    {
        $this->locationAsked = true;

        try {
            Geolocation::checkPermissions()->get();
        } catch (\Throwable) {
            // Not running on a native device — nothing to ask.
        }
    }

    #[OnNative(PermissionStatusReceived::class)]
    public function onLocationPermissionStatus(string $location = '', string $coarseLocation = '', string $fineLocation = ''): void
    {
        if (! $this->locationAsked) {
            return;
        }

        $this->rememberLocationPermission($location);

        if ($location === 'granted') {
            $this->fetchCurrentPosition();

            return;
        }

        if ($location === 'not_determined') {
            Geolocation::requestPermissions()->get();

            return;
        }

        $this->locationAsked = false;
        $this->onLocationPermissionDenied($location);
    }

    #[OnNative(PermissionRequestResult::class)]
    public function onLocationPermissionRequestResult(string $location = '', string $coarseLocation = '', string $fineLocation = '', ?string $error = null): void
    {
        if (! $this->locationAsked) {
            return;
        }

        $this->rememberLocationPermission($location);

        if ($location === 'granted') {
            $this->fetchCurrentPosition();

            return;
        }

        // iOS fires this event from CLLocationManager's init callback too —
        // with not_determined, BEFORE the player has answered the prompt.
        // Treating that as a denial showed "location access is off" in the
        // happy path, right on top of the OS permission dialog.
        if ($location === 'not_determined' || $location === '') {
            return;
        }

        $this->locationAsked = false;
        $this->onLocationPermissionDenied($location);
    }

    /**
     * Screens that show the permission state (Settings) declare a
     * $locationPermission property; everyone else ignores this.
     */
    protected function rememberLocationPermission(string $status): void
    {
        if (property_exists($this, 'locationPermission')) {
            $this->locationPermission = $status;
        }
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
        $this->locationAsked = false;
        $this->awaitingFix = true;

        Geolocation::getCurrentPosition($this->wantsFineLocation());
    }

    /**
     * True once for the fix this screen asked for; a fix someone else asked
     * for returns false and is ignored.
     */
    protected function takeAwaitedFix(): bool
    {
        $awaited = $this->awaitingFix;
        $this->awaitingFix = false;

        return $awaited;
    }

    /**
     * Tell the user why nothing happened. Screens may override for a different
     * message or a settings deep link.
     */
    protected function onLocationPermissionDenied(string $status): void
    {
        $this->dispatch('validation-notice', message: $status === 'permanently_denied'
            ? __('general.location_permission_blocked')
            : __('general.location_permission_denied'));
    }
}
