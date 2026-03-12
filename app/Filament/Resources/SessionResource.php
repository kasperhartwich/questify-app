<?php

namespace App\Filament\Resources;

use App\Enums\SessionStatus;
use App\Filament\Resources\SessionResource\Pages;
use App\Models\QuestSession;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SessionResource extends Resource
{
    protected static ?string $model = QuestSession::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Gameplay';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $modelLabel = 'Session';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Session Details')
                    ->schema([
                        TextEntry::make('quest.title')
                            ->label('Quest'),
                        TextEntry::make('host.name')
                            ->label('Host'),
                        TextEntry::make('join_code')
                            ->label('Join Code')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('play_mode')
                            ->label('Play Mode')
                            ->badge(),
                        TextEntry::make('started_at')
                            ->dateTime()
                            ->placeholder('Not started'),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('Not completed'),
                    ])
                    ->columns(2),
                Section::make('Participants')
                    ->schema([
                        RepeatableEntry::make('participants')
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Player'),
                                TextEntry::make('display_name')
                                    ->label('Display Name'),
                                TextEntry::make('score')
                                    ->numeric(),
                                TextEntry::make('finished_at')
                                    ->dateTime()
                                    ->placeholder('In progress'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quest.title')
                    ->label('Quest')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Host')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('join_code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (SessionStatus $state): string => match ($state) {
                        SessionStatus::Waiting => 'gray',
                        SessionStatus::InProgress => 'info',
                        SessionStatus::Completed => 'success',
                        SessionStatus::Abandoned => 'danger',
                    }),
                Tables\Columns\TextColumn::make('play_mode')
                    ->label('Mode')
                    ->badge(),
                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Players')
                    ->counts('participants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
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
                    ->options(SessionStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
            'view' => Pages\ViewSession::route('/{record}'),
        ];
    }
}
