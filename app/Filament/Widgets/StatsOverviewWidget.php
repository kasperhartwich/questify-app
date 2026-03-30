<?php

namespace App\Filament\Widgets;

use App\Enums\ModerationStatus;
use App\Enums\QuestStatus;
use App\Enums\SessionStatus;
use App\Models\ModerationFlag;
use App\Models\Quest;
use App\Models\QuestSession;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::query()->count())
                ->icon('heroicon-o-users'),
            Stat::make('Published Quests', Quest::query()->where('status', QuestStatus::Published)->count())
                ->icon('heroicon-o-map'),
            Stat::make('Active Sessions Today', QuestSession::query()
                ->whereIn('status', [SessionStatus::Waiting, SessionStatus::Active])
                ->whereDate('created_at', today())
                ->count())
                ->icon('heroicon-o-play-circle'),
            Stat::make('Pending Moderation', ModerationFlag::query()->where('status', ModerationStatus::Pending)->count())
                ->icon('heroicon-o-flag')
                ->color('warning'),
        ];
    }
}
