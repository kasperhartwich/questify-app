<?php

namespace App\Filament\Resources\QuestResource\Pages;

use App\Enums\QuestStatus;
use App\Filament\Resources\QuestResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuests extends ListRecords
{
    protected static string $resource = QuestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', QuestStatus::Draft)),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', QuestStatus::Published)),
            'archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', QuestStatus::Archived)),
        ];
    }
}
