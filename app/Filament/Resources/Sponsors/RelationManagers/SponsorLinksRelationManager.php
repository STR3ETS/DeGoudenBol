<?php

namespace App\Filament\Resources\Sponsors\RelationManagers;

use App\Domain\Commerce\Models\SponsorLink;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SponsorLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    protected static ?string $modelLabel = 'koppeling';

    protected static ?string $pluralModelLabel = 'koppelingen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return '"Bakt met"-koppelingen';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('company'))
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('company.name')->label('Deelnemer')->weight('bold'),
                TextColumn::make('requested_at')->label('Aangevraagd')->dateTime('d-m-Y H:i'),
                TextColumn::make('confirmed_by_company_at')->label('Bevestigd door deelnemer')->dateTime('d-m-Y H:i')->placeholder(fn (SponsorLink $record) => $record->declined_at ? 'Geweigerd' : 'Wacht op deelnemer'),
            ]);
    }
}
