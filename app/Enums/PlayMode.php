<?php

namespace App\Enums;

enum PlayMode: string
{
    case Solo = 'solo';
    case CompetitiveIndividual = 'competitive_individual';
    case CompetitiveTeams = 'competitive_teams';
}
