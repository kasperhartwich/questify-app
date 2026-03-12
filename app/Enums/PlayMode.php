<?php

namespace App\Enums;

enum PlayMode: string
{
    case Solo = 'solo';
    case Competitive = 'competitive';
    case Cooperative = 'cooperative';
}
