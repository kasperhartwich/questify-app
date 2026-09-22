<?php

namespace App\Services;

use App\Services\Api\ApiCache;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\System;

class TokenStorage
{
    private const KEY = 'questify_api_token';

    /**
     * On device the token lives in the keychain (the secure-storage plugin),
     * with an encrypted file in the app container as a fallback. It is not
     * copied into the session there: session files are plain text in the same
     * container, so a copy would undo the encryption. The webview's session
     * cookie dies with the process anyway.
     */
    public static function get(): ?string
    {
        if (self::isMobile()) {
            return SecureStorage::get(self::KEY)
                ?? self::readFile();
        }

        return session(self::KEY);
    }

    public static function set(string $token): void
    {
        if (self::isMobile()) {
            SecureStorage::set(self::KEY, $token);
            self::writeFile($token);
            session()->forget(self::KEY);

            return;
        }

        session()->put(self::KEY, $token);
    }

    /**
     * The API rejected the token: drop every copy of it, the identity cached
     * for it and the session. Flushing only the session left the keychain and
     * file copies behind, so the guard kept treating the player as signed in
     * and the login screen bounced them straight back into the app.
     */
    public static function signOut(): void
    {
        self::forget();
        ApiCache::forgetPrefix('auth:me');
        session()->flush();
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
