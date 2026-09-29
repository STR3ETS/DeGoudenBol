<?php

namespace App\Filament\Resources\TermsVersions\Tables;

use App\Domain\Edition\Enums\TermsType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TermsVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('type')->label('Soort')->badge(),
                TextColumn::make('version')->label('Versie'),
                TextColumn::make('title')->label('Titel')->searchable(),
                TextColumn::make('edition.year')->label('Editie')->placeholder('Alle'),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m-Y H:i')->placeholder('Concept')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Soort')->options(TermsType::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
