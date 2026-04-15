<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verifyEmail')
                ->label('Verify Email')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verify User Email')
                ->modalDescription(fn (User $record): string => "Mark {$record->email} as verified?")
                ->action(function (User $record): void {
                    $record->forceFill(['email_verified_at' => now()])->save();

                    Notification::make()
                        ->success()
                        ->title('Email verified')
                        ->send();
                })
                ->hidden(fn (User $record): bool => $record->hasVerifiedEmail()),
            Actions\Action::make('changePassword')
                ->label('Change Password')
                ->icon('heroicon-o-key')
                ->form([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->label('New Password'),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password')
                        ->label('Confirm Password'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Change User Password')
                ->modalDescription(fn (User $record): string => "Set a new password for {$record->name}.")
                ->action(function (User $record, array $data): void {
                    $record->update(['password' => $data['password']]);

                    Notification::make()
                        ->success()
                        ->title('Password updated')
                        ->send();
                }),
        ];
    }
}
