<?php

namespace App\Enums;

enum WrongAnswerBehaviour: string
{
    case RetryFree = 'retry_free';
    case RetryPenalty = 'retry_penalty';
    case Lockout = 'lockout';
    case ThreeStrikesHint = 'three_strikes_hint';
}
