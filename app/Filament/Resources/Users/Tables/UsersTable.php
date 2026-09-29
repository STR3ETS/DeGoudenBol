<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rollen')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StaffRole::tryFrom($state)?->getLabel() ?? $state),
                IconColumn::make('mfa')
                    ->label('2FA')
                    ->state(fn (User $record): bool => filled($record->app_authentication_secret))
                    ->boolean(),
                IconColumn::make('is_active')->label('Actief')->boolean(),
                TextColumn::make('last_login_at')->label('Laatste login')->dateTime('d-m-Y H:i')->placeholder('–')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Rol')->relationship('roles', 'name')->options(
                    collect(StaffRole::cases())->mapWithKeys(fn (StaffRole $role) => [$role->value => $role->getLabel()])->all()
                ),
                TernaryFilter::make('is_active')->label('Actief'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
