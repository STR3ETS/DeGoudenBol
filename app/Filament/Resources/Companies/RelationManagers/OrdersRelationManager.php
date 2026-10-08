<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Participants\Models\Entry;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Orders en facturen van dit bedrijf; alleen voor wie orders mag zien (Financiën).
 */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'orders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Orders en facturen';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrderResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['edition', 'invoice']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('edition.name')->label('Editie'),
                TextColumn::make('orderable_type')->label('Soort')->formatStateUsing(fn (string $state) => match ($state) {
                    Entry::class => 'Deelname',
                    Sponsor::class => 'Sponsoring',
                    default => class_basename($state),
                }),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('total_cents')->label('Totaal incl. btw')->formatStateUsing(fn (int $state) => Money::format($state))->alignEnd(),
                TextColumn::make('invoice.number')->label('Factuur')->placeholder('–'),
                TextColumn::make('invoice.status')->label('Factuurstatus')->badge()->placeholder('–'),
                TextColumn::make('paid_at')->label('Betaald op')->dateTime('d-m-Y H:i')->placeholder('–'),
                TextColumn::make('created_at')->label('Aangemaakt')->dateTime('d-m-Y H:i'),
            ])
            ->emptyStateHeading('Geen orders')
            ->emptyStateDescription('Er is nog niets besteld of gefactureerd voor dit bedrijf.');
    }
}
