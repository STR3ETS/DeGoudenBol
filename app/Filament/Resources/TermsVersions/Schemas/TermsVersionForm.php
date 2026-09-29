<?php

namespace App\Filament\Resources\TermsVersions\Schemas;

use App\Domain\Edition\Enums\TermsType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TermsVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('type')
                    ->label('Soort')
                    ->options(TermsType::class)
                    ->required(),
                Select::make('edition_id')
                    ->label('Editie')
                    ->relationship('edition', 'name')
                    ->helperText('Leeg = geldt voor alle edities.'),
                TextInput::make('version')
                    ->label('Versie')
                    ->required()
                    ->maxLength(32)
                    ->helperText('Bijvoorbeeld 2026.1. Ieder akkoord verwijst naar een versie; wijzig een gepubliceerde versie niet, maak een nieuwe.'),
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('body')
                    ->label('Tekst (van de jurist)')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('published_at')
                    ->label('Gepubliceerd vanaf')
                    ->seconds(false)
                    ->helperText('Leeg = concept, nog niet zichtbaar.'),
            ]);
    }
}
