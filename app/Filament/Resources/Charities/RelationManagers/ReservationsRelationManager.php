<?php

namespace App\Filament\Resources\Charities\RelationManagers;

use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $modelLabel = 'reservering';

    protected static ?string $pluralModelLabel = 'reserveringen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return '10%-reserveringen';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['orderLine.order', 'payer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('orderLine.order.number')->label('Order'),
                TextColumn::make('orderLine.description')->label('Regel')->limit(50),
                TextColumn::make('payer.name')->label('Betaler')->placeholder('–'),
                TextColumn::make('basis_cents')->label('Grondslag excl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('amount_cents')->label('Gereserveerd')->formatStateUsing(fn (int $state) => Money::format($state))->weight('bold'),
                TextColumn::make('created_at')->label('Ontvangen')->dateTime('d-m-Y'),
            ]);
    }
}
