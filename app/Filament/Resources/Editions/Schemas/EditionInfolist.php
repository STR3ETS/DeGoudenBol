<?php

namespace App\Filament\Resources\Editions\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Algemeen')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('year')->label('Jaar'),
                        TextEntry::make('name')->label('Naam'),
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('status')->label('Status')->badge(),
                    ]),
                Section::make('Datums')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('registration_opens_at')->label('Inschrijving opent')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('registration_closes_at')->label('Inschrijving sluit')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('first_test_day')->label('Eerste testdag')->date('d-m-Y')->placeholder('–'),
                        TextEntry::make('last_test_day')->label('Laatste testdag')->date('d-m-Y')->placeholder('–'),
                        TextEntry::make('freeze_at')->label('Bevriezing')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('main_publication_at')->label('Hoofdpublicatie')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('final_test_day')->label('Finaletest')->date('d-m-Y')->placeholder('–'),
                        TextEntry::make('national_result_at')->label('Landelijke uitslag')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('last_redeem_day')->label('Laatste verzilverdag')->date('d-m-Y')->placeholder('–'),
                    ]),
                Section::make('Instellingen')
                    ->collapsible()
                    ->schema([
                        KeyValueEntry::make('settings')
                            ->label('')
                            ->keyLabel('Instelling')
                            ->valueLabel('Waarde'),
                    ]),
            ]);
    }
}
