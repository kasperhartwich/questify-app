<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\System;

class TokenStorage
{
    private const KEY = 'questify_api_token';

    /**
     * The keychain would be the right home, but NativePHP v4 ships no native
     * handler for the SecureStorage bridge on either platform — every call is
     * a silent no-op, which is why signing in never survived a cold start
     * (cookies die with the process: the shell's webview uses a
     * non-persistent data store). Until the bridge exists, the token lives
     * encrypted in the app container, which persists across restarts and
     * updates; APP_KEY itself is kept in the real keychain by the shell, so
     * the file is ciphertext at rest.
     */
    public static function get(): ?string
    {
        if (self::isMobile()) {
            return SecureStorage::get(self::KEY)
                ?? self::readFile()
                ?? session(self::KEY);
        }

        return session(self::KEY);
    }

    public static function set(string $token): void
    {
        if (self::isMobile()) {
            SecureStorage::set(self::KEY, $token);
            self::writeFile($token);
        }

        session()->put(self::KEY, $token);
    }

    public static function forget(): void
    {
        if (self::isMobile()) {
            SecureStorage::delete(self::KEY);
            @unlink(self::filePath());
        }

        session()->forget(self::KEY);
    }

    public static function has(): bool
    {
        return self::get() !== null;
    }

    private static function writeFile(string $token): void
    {
        try {
            // The app container ships without storage/app — create the whole
            // path or file_put_contents fails silently and nothing persists.
            $dir = dirname(self::filePath());

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents(self::filePath(), Crypt::encryptString($token));
        } catch (\Throwable) {
            // Storage may be momentarily unavailable during boot; the session
            // still carries the token for this run.
        }
    }

    private static function readFile(): ?string
    {
        $path = self::filePath();

        if (! is_file($path)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) file_get_contents($path)) ?: null;
        } catch (DecryptException|\Throwable) {
            // A rotated APP_KEY or corrupt file: treat as signed out.
            @unlink($path);

            return null;
        }
    }

    private static function filePath(): string
    {
        return storage_path('app/private/api-token');
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
