<?php

namespace App\Enums;

enum WrongAnswerBehaviour: string
{
    case Retry = 'retry';
    case ShowAnswer = 'show_answer';
    case SkipToNext = 'skip_to_next';
}
