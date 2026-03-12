<?php

namespace App\Filament\Widgets;

use App\Enums\QuestStatus;
use App\Filament\Resources\QuestResource;
use App\Models\Quest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPendingQuestsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Pending Quests';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quest::query()
                    ->where('status', QuestStatus::Draft)
                    ->with(['creator', 'category'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creator'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('difficulty')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Quest $record): string => QuestResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated([5]);
    }
}
