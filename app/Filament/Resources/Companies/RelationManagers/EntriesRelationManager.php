<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Domain\Participants\Models\Entry;
use App\Filament\Resources\Entries\EntryResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Inschrijvingen van dit bedrijf per editie, met status, pakket en aanleverslot.
 */
class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $modelLabel = 'inschrijving';

    protected static ?string $pluralModelLabel = 'inschrijvingen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Inschrijvingen';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return EntryResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['edition', 'package', 'province', 'deliverySlot']))
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('edition.name')->label('Editie'),
                TextColumn::make('public_name')->label('Naam op de site')->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('province.name')->label('Provincie'),
                TextColumn::make('package.name')->label('Pakket')->placeholder('–'),
                TextColumn::make('deliverySlot.starts_at')->label('Aanleverslot')->dateTime('D d-m H:i')->placeholder('–'),
                TextColumn::make('registered_at')->label('Aangemeld')->dateTime('d-m-Y H:i'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Bekijken')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Entry $record) => EntryResource::getUrl('index', ['tableSearch' => $record->public_name])),
            ])
            ->emptyStateHeading('Nog geen inschrijvingen')
            ->emptyStateDescription('Dit bedrijf heeft zich nog voor geen enkele editie aangemeld.');
    }
}
