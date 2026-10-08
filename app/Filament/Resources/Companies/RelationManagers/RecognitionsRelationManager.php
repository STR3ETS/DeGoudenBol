<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Recognitions\RecognitionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Erkenningen en badges die dit bedrijf heeft gekregen.
 */
class RecognitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'recognitions';

    protected static ?string $modelLabel = 'badge';

    protected static ?string $pluralModelLabel = 'badges';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Badges';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return RecognitionResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['edition', 'province']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')->label('Soort')->badge(),
                TextColumn::make('edition.name')->label('Editie'),
                TextColumn::make('province.name')->label('Provincie')->placeholder('–'),
                TextColumn::make('code')->label('Code')->copyable()->fontFamily('mono'),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('valid_until')->label('Geldig tot')->date('d-m-Y')->placeholder('–'),
                TextColumn::make('embargo_until')->label('Embargo tot')->dateTime('d-m-Y H:i')->placeholder('–'),
            ])
            ->emptyStateHeading('Nog geen badges')
            ->emptyStateDescription('Badges komen bij aanmelding, na de test en bij een plek op de lijsten.');
    }
}
