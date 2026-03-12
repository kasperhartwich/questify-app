<?php

namespace App\Filament\Resources;

use App\Enums\ModerationStatus;
use App\Enums\QuestStatus;
use App\Filament\Resources\QuestResource\Pages;
use App\Models\Quest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Quest Details')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('creator.name')
                            ->label('Creator'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('difficulty')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('visibility')
                            ->badge(),
                        TextEntry::make('play_mode')
                            ->label('Play Mode')
                            ->badge(),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        TextEntry::make('wrong_answer_behaviour')
                            ->label('Wrong Answer Behaviour'),
                        TextEntry::make('time_limit_per_question')
                            ->label('Time Limit (seconds)')
                            ->placeholder('No limit'),
                        TextEntry::make('max_participants')
                            ->placeholder('Unlimited'),
                        TextEntry::make('shuffle_questions')
                            ->label('Shuffle Questions')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('shuffle_answers')
                            ->label('Shuffle Answers')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(3),
                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('checkpoints_count')
                            ->label('Checkpoints')
                            ->state(fn (Quest $record): int => $record->checkpoints()->count()),
                        TextEntry::make('sessions_count')
                            ->label('Sessions')
                            ->state(fn (Quest $record): int => $record->sessions()->count()),
                        TextEntry::make('ratings_avg')
                            ->label('Average Rating')
                            ->state(fn (Quest $record): string => number_format((float) $record->ratings()->avg('rating'), 1)),
                        TextEntry::make('published_at')
                            ->dateTime()
                            ->placeholder('Not published'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creator')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (QuestStatus $state): string => match ($state) {
                        QuestStatus::Draft => 'gray',
                        QuestStatus::Published => 'success',
                        QuestStatus::Archived => 'warning',
                    }),
                Tables\Columns\TextColumn::make('difficulty')
                    ->badge(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(QuestStatus::class),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Quest $record): void {
                        $record->update([
                            'status' => QuestStatus::Published,
                            'published_at' => now(),
                        ]);

                        $record->moderationFlags()
                            ->where('status', ModerationStatus::Pending)
                            ->update([
                                'status' => ModerationStatus::Approved,
                                'moderator_id' => auth()->id(),
                                'resolved_at' => now(),
                            ]);
                    })
                    ->visible(fn (Quest $record): bool => $record->status !== QuestStatus::Published),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Quest $record): void {
                        $record->update([
                            'status' => QuestStatus::Archived,
                        ]);

                        $record->moderationFlags()
                            ->where('status', ModerationStatus::Pending)
                            ->update([
                                'status' => ModerationStatus::Rejected,
                                'moderator_id' => auth()->id(),
                                'resolved_at' => now(),
                            ]);
                    })
                    ->visible(fn (Quest $record): bool => $record->status !== QuestStatus::Archived),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuests::route('/'),
            'view' => Pages\ViewQuest::route('/{record}'),
        ];
    }
}
