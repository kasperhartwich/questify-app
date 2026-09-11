<?php

namespace App\Enums;

use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;

enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';
    case Microsoft = 'microsoft';

    /**
     * Whether this provider can actually be used: credentials are present
     * and a Socialite driver exists for it (built-in or registered by a
     * provider package).
     */
    public function isConfigured(): bool
    {
        if (blank(config("services.{$this->value}.client_id"))) {
            return false;
        }

        try {
            Socialite::driver($this->value);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
