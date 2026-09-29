<?php

namespace App\Filament\Resources\PublicationBatches\RelationManagers;

use App\Domain\Ranking\Models\PublicationItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Uitslagen in de batch: naam, provincie, cijfer en weergave. Alleen ter controle; niets is hier te wijzigen.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $modelLabel = 'uitslag';

    protected static ?string $pluralModelLabel = 'uitslagen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Uitslagen in deze batch';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.company', 'province']))
            ->defaultSort('id')
            ->columns([
                TextColumn::make('entry.public_name')->label('Naam op de site')->weight('bold'),
                TextColumn::make('entry.company.name')->label('Bedrijf'),
                TextColumn::make('province.name')->label('Provincie'),
                TextColumn::make('total')->label('Cijfer')->state(fn (PublicationItem $record) => number_format($record->total(), 1, ',', '.'))->weight('bold'),
                TextColumn::make('card_count')->label('Kaarten')->state(fn (PublicationItem $record) => $record->result_snapshot['card_count'] ?? '–'),
                TextColumn::make('visibility')->label('Weergave')->badge(),
                TextColumn::make('entry.status')->label('Status inschrijving')->badge(),
            ]);
    }
}
