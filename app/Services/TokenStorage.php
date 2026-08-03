<?php

namespace App\Services;

use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\System;

class TokenStorage
{
    private const KEY = 'questify_api_token';

    public static function get(): ?string
    {
        if (self::isMobile()) {
            // Fall back to the session when secure storage yields nothing: set() always
            // mirrors the token into the session, so this keeps authenticated API calls
            // working even if the secure-storage bridge returns null on-device.
            return SecureStorage::get(self::KEY) ?? session(self::KEY);
        }

        return session(self::KEY);
    }

    public static function set(string $token): void
    {
        if (self::isMobile()) {
            SecureStorage::set(self::KEY, $token);
        }

        session()->put(self::KEY, $token);
    }

    public static function forget(): void
    {
        if (self::isMobile()) {
            SecureStorage::delete(self::KEY);
        }

        session()->forget(self::KEY);
    }

    public static function has(): bool
    {
        return self::get() !== null;
    }

    private static function isMobile(): bool
    {
        try {
            return System::isMobile();
        } catch (\Throwable) {
            return false;
        }
    }
}
