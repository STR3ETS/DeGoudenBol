<?php

namespace App\Filament\Resources\ScoringModels\Tables;

use App\Domain\Edition\Models\ScoringModel;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScoringModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('criteria', 'max_points')->withCount('criteria'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('edition.year')->label('Editie')->sortable(),
                TextColumn::make('name')->label('Naam')->searchable(),
                TextColumn::make('product_type')->label('Productsoort'),
                TextColumn::make('version')->label('Versie'),
                TextColumn::make('criteria_count')->label('Onderdelen'),
                TextColumn::make('criteria_sum_max_points')
                    ->label('Totaal punten')
                    ->badge()
                    ->color(fn (?int $state): string => (int) $state === ScoringModel::TOTAL_POINTS ? 'success' : 'danger')
                    ->formatStateUsing(fn (?int $state): string => ((int) $state).' / '.ScoringModel::TOTAL_POINTS),
                IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
