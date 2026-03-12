<?php

namespace App\Enums;

enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';
    case Microsoft = 'microsoft';
}
