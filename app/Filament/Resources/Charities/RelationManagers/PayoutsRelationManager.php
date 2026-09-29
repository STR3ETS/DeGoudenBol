<?php

namespace App\Filament\Resources\Charities\RelationManagers;

use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $modelLabel = 'uitbetaling';

    protected static ?string $pluralModelLabel = 'uitbetalingen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Uitbetalingen';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('paid_at')->label('Betaald op')->date('d-m-Y'),
                TextColumn::make('amount_cents')->label('Bedrag')->formatStateUsing(fn (int $state) => Money::format($state))->weight('bold'),
                TextColumn::make('reference')->label('Kenmerk')->placeholder('–'),
                TextColumn::make('note')->label('Notitie')->placeholder('–')->limit(60),
            ]);
    }
}
