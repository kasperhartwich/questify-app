<?php

namespace App\Filament\Resources;

use App\Enums\ModerationStatus;
use App\Filament\Resources\ModerationFlagResource\Pages;
use App\Models\ModerationFlag;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ModerationFlagResource extends Resource
{
    protected static ?string $model = ModerationFlag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Flags';

    protected static ?string $modelLabel = 'Moderation Flag';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('flaggable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('flaggable_id')
                    ->label('Item ID'),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'warning',
                        ModerationStatus::Approved => 'success',
                        ModerationStatus::Rejected => 'danger',
                    }),
                Tables\Columns\TextColumn::make('moderator.name')
                    ->label('Resolved By')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ModerationStatus::class)
                    ->default(ModerationStatus::Pending->value),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('resolution_note')
                            ->label('Resolution Note')
                            ->required(),
                    ])
                    ->action(function (ModerationFlag $record, array $data): void {
                        $record->update([
                            'status' => ModerationStatus::Approved,
                            'moderator_id' => auth()->id(),
                            'resolution_note' => $data['resolution_note'],
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (ModerationFlag $record): bool => $record->status === ModerationStatus::Pending),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('resolution_note')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (ModerationFlag $record, array $data): void {
                        $record->update([
                            'status' => ModerationStatus::Rejected,
                            'moderator_id' => auth()->id(),
                            'resolution_note' => $data['resolution_note'],
                            'resolved_at' => now(),
                        ]);
                    })
                    ->visible(fn (ModerationFlag $record): bool => $record->status === ModerationStatus::Pending),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationFlags::route('/'),
        ];
    }
}
