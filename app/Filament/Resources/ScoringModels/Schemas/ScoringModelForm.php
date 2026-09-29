<?php

namespace App\Filament\Resources\ScoringModels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ScoringModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('edition_id')
                    ->label('Editie')
                    ->relationship('edition', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255),
                TextInput::make('product_type')
                    ->label('Productsoort')
                    ->default('oliebol')
                    ->required()
                    ->helperText('Later ook appelbeignet of ander gebak zonder herbouw.'),
                TextInput::make('version')
                    ->label('Versie')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Actief voor deze editie')
                    ->helperText('Per editie en productsoort is één model actief. De onderdelen beheer je op het tabblad hieronder; samen moeten ze 100 punten zijn.')
                    ->columnSpanFull(),
            ]);
    }
}
