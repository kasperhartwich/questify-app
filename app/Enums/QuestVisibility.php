<?php

namespace App\Enums;

enum QuestVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case School = 'school';
}
