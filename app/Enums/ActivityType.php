<?php

namespace App\Enums;

enum ActivityType: string
{
    case QuestCreated = 'quest_created';
    case QuestPublished = 'quest_published';
    case QuestCompleted = 'quest_completed';
    case QuestShared = 'quest_shared';
    case QuestRated = 'quest_rated';
    case QuestFavourited = 'quest_favourited';
}
