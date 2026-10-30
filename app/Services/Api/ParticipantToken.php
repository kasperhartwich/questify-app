<?php

namespace App\Services\Api;

/**
 * The participant token the API hands out once at join (QU-40), kept in the
 * app session per session code and sent back as a header on the calls that
 * act as, or read for, that participant.
 */
class ParticipantToken
{
    public const HEADER = 'X-Participant-Token';

    private const KEY = 'questify_participant_token';

    /**
     * Keep the token from a join response. A response without one (an older
     * API) drops any token held for that code.
     */
    public static function remember(string $code, ?string $token): void
    {
        if ($token === null || $token === '') {
            session()->forget(self::key($code));

            return;
        }

        session()->put(self::key($code), $token);
    }

    public static function get(string $code): ?string
    {
        $token = session(self::key($code));

        return is_string($token) && $token !== '' ? $token : null;
    }

    /** @return array<string, string> */
    public static function headers(string $code): array
    {
        $token = self::get($code);

        return $token === null ? [] : [self::HEADER => $token];
    }

    /** Forget every session's token, together with the participant id. */
    public static function forgetAll(): void
    {
        session()->forget(self::KEY);
    }

    private static function key(string $code): string
    {
        return self::KEY.'.'.strtoupper($code);
    }
}
