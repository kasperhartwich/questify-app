<?php

use App\Exceptions\Api\ApiException;
use App\Livewire\Concerns\WithApiClient;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\PushNotification\TokenGenerated;
use Native\Mobile\Facades\PushNotifications;

new class extends Component
{
    use WithApiClient;

    public function mount(): void
    {
        if (! session('questify_api_token')) {
            return;
        }

        $this->enrollIfNeeded();
    }

    public function enrollIfNeeded(): void
    {
        if (! class_exists(PushNotifications::class)) {
            return;
        }

        try {
            $status = PushNotifications::checkPermission();

            if ($status === 'not_determined') {
                PushNotifications::enroll();
            } elseif ($status === 'granted') {
                PushNotifications::getToken();
            }
        } catch (\Throwable) {
            // Not running on native device
        }
    }

    #[OnNative(TokenGenerated::class)]
    public function handlePushToken(string $token): void
    {
        if (! session('questify_api_token')) {
            return;
        }

        try {
            $platform = str_contains(strtolower(PHP_OS), 'darwin') ? 'ios' : 'android';
            $this->api->user()->storeFcmToken($token, $platform);
        } catch (ApiException) {
            // Silently handle — token registration is non-critical
        }
    }
};
?>

<div>
    {{-- Invisible push notification manager --}}
</div>
