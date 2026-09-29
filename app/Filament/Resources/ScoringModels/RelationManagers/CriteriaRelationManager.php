<?php

namespace App\Filament\Resources\ScoringModels\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * De onderdelen van het 100-puntenmodel, met toelichting voor het panel en de tiebreak-volgorde.
 */
class CriteriaRelationManager extends RelationManager
{
    protected static string $relationship = 'criteria';

    protected static ?string $modelLabel = 'onderdeel';

    protected static ?string $pluralModelLabel = 'onderdelen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Onderdelen (samen 100 punten)';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->alphaDash()
                    ->maxLength(64)
                    ->helperText('Stabiele sleutel voor scorekaarten en tiebreak, bijvoorbeeld smaak.'),
                TextInput::make('max_points')
                    ->label('Maximum punten')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),
                TextInput::make('tie_break_rank')
                    ->label('Tiebreak-volgorde')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('1 = eerste beslisser bij gelijke stand; leeg = telt niet mee.'),
                Textarea::make('description')
                    ->label('Toelichting voor het panel')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('sort')->label('#'),
                TextColumn::make('name')->label('Onderdeel'),
                TextColumn::make('code')->label('Code')->fontFamily('mono'),
                TextColumn::make('max_points')->label('Max. punten')->summarize(Sum::make()->label('Totaal')),
                TextColumn::make('tie_break_rank')->label('Tiebreak')->placeholder('–'),
                TextColumn::make('description')->label('Toelichting')->limit(60)->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label('Onderdeel toevoegen'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
