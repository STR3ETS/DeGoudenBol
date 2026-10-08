<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\ParticipantUsers\ParticipantUserResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Accounts die bij dit bedrijf horen, met hun rol in het portaal.
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $modelLabel = 'account';

    protected static ?string $pluralModelLabel = 'accounts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Accounts';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ParticipantUserResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Naam')->weight('bold'),
                TextColumn::make('email')->label('E-mail')->copyable(),
                TextColumn::make('phone')->label('Telefoon')->placeholder('–'),
                TextColumn::make('pivot.role')->label('Rol')->badge(),
                TextColumn::make('created_at')->label('Account sinds')->dateTime('d-m-Y'),
            ])
            ->emptyStateHeading('Geen accounts')
            ->emptyStateDescription('Nog niemand kan voor dit bedrijf inloggen op het portaal.');
    }
}
