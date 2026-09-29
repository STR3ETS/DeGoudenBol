<?php

namespace App\Filament\Resources\Editions\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EditionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')->label('Jaar')->sortable(),
                TextColumn::make('name')->label('Naam')->searchable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('registration_opens_at')->label('Inschrijving opent')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('first_test_day')->label('Eerste testdag')->date('d-m-Y')->sortable(),
                TextColumn::make('last_test_day')->label('Laatste testdag')->date('d-m-Y')->sortable(),
                TextColumn::make('freeze_at')->label('Bevriezing')->dateTime('d-m-Y H:i')->sortable()->toggleable(),
                TextColumn::make('main_publication_at')->label('Hoofdpublicatie')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('provinces_count')->label('Provincies')->counts('provinces'),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime('d-m-Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
