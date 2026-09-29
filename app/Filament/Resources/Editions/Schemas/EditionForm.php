<?php

namespace App\Filament\Resources\Editions\Schemas;

use App\Domain\Edition\Enums\CharityBasis;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\FinalistFallback;
use App\Domain\Edition\Enums\LogisticsMode;
use App\Domain\Edition\Enums\ParticipationModel;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EditionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Editie')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Algemeen')->schema(self::general())->columns(2),
                        Tab::make('Datums')->schema(self::dates())->columns(2),
                        Tab::make('Instellingen')->schema(self::settings()),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function general(): array
    {
        return [
            TextInput::make('year')
                ->label('Jaar')
                ->numeric()
                ->minValue(2026)
                ->maxValue(2099)
                ->required()
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if (filled($state)) {
                        $set('slug', $state);
                        $set('name', "De Gouden Bol {$state}");
                    }
                }),
            Select::make('status')
                ->label('Status')
                ->options(EditionStatus::class)
                ->default(EditionStatus::Draft)
                ->required(),
            TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug (URL)')
                ->required()
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->helperText('Wordt gebruikt in /editie/{slug}.'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function dates(): array
    {
        return [
            Section::make('Inschrijving')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('registration_opens_at')->label('Inschrijving opent')->seconds(false),
                    DateTimePicker::make('registration_closes_at')->label('Inschrijving sluit')->seconds(false)
                        ->helperText('Leeg = tot de laatste testdag.'),
                ]),
            Section::make('Testperiode en bevriezing (besluit 1)')
                ->columns(2)
                ->schema([
                    DatePicker::make('first_test_day')->label('Eerste testdag'),
                    DatePicker::make('last_test_day')->label('Laatste testdag (sluiting Voorlijst)'),
                    DateTimePicker::make('freeze_at')->label('Bevriezing')->seconds(false)
                        ->helperText('Definitieve Top 10, provinciewinnaars en finale-inschrijvingen ontstaan in één transactie.'),
                    DateTimePicker::make('main_publication_at')->label('Hoofdpublicatie')->seconds(false)
                        ->helperText('Embargo op badges, kits en mails loopt tot dit moment.'),
                ]),
            Section::make('Finale en cadeaubonnen')
                ->columns(2)
                ->schema([
                    DatePicker::make('final_test_day')->label('Finaletest'),
                    DateTimePicker::make('national_result_at')->label('Landelijke uitslag')->seconds(false),
                    DatePicker::make('last_redeem_day')->label('Laatste verzilverdag cadeaubonnen')
                        ->helperText('Daarna vervalt iedere bon automatisch.'),
                ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function settings(): array
    {
        return [
            Section::make('Capaciteit en lijsten')
                ->description('Besluiten 2 en 13.')
                ->columns(4)
                ->schema([
                    TextInput::make('settings.capacity_per_province')->label('Plekken per provincie')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.provincial_list_length')->label('Lengte provinciale Top-lijst')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.national_list_length')->label('Lengte landelijke lijst')->numeric()->minValue(1)->required()
                        ->helperText('Top 5 (dossier) of Top 10 (plan).'),
                    TextInput::make('settings.max_finalists')->label('Maximum finalisten')->numeric()->minValue(1)->required(),
                ]),
            Section::make('Test- en rekenregels')
                ->description('Besluit 19: bijstellen na de proefrun.')
                ->columns(3)
                ->schema([
                    TextInput::make('settings.publish_threshold')->label('Publicatiedrempel (cijfer)')->numeric()->step(0.1)->minValue(0)->maxValue(10)->required(),
                    TextInput::make('settings.min_valid_cards')->label('Minimum geldige kaarten per monster')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.max_samples_per_session')->label('Maximum monsters per sessie')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.freshness_window_minutes')->label('Versheidsvenster (minuten)')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.outlier_deviation_points')->label('Afwijkingsvlag (punten van mediaan)')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.rounding_decimals')->label('Decimalen eindcijfer')->numeric()->minValue(0)->maxValue(2)->required(),
                    TagsInput::make('settings.tie_break_order')
                        ->label('Beslisvolgorde bij gelijke stand (criteriumcodes)')
                        ->placeholder('Code toevoegen…')
                        ->columnSpanFull()
                        ->helperText('Volgorde uit het dossier: smaak → structuur_luchtigheid → versheid → vulling_verhouding.'),
                ]),
            Section::make('Publicatieritme')
                ->description('Besluit 20.')
                ->columns(3)
                ->schema([
                    CheckboxList::make('settings.publication_days')
                        ->label('Publicatiedagen')
                        ->options([
                            'monday' => 'Maandag',
                            'tuesday' => 'Dinsdag',
                            'wednesday' => 'Woensdag',
                            'thursday' => 'Donderdag',
                            'friday' => 'Vrijdag',
                            'saturday' => 'Zaterdag',
                            'sunday' => 'Zondag',
                        ])
                        ->columns(2)
                        ->columnSpan(2),
                    TimePicker::make('settings.publication_time')->label('Publicatietijd')->seconds(false)->required(),
                    DatePicker::make('settings.quiet_period_from')->label('Stille periode vanaf')
                        ->helperText('Resultaten vanaf deze dag pas zichtbaar bij de hoofdpublicatie.'),
                    Toggle::make('settings.show_panel_after_edition')->label('Panelleden pas na de editie tonen')->inline(false),
                    Select::make('settings.public_subscores_scope')
                        ->label('Deelscores openbaar voor')
                        ->options([
                            'none' => 'Niemand',
                            'top10_final' => 'Alleen de Definitieve Top 10',
                            'all_published' => 'Iedereen vanaf de drempel',
                        ])
                        ->required()
                        ->helperText('Besluit 6.'),
                ]),
            Section::make('Deelname en logistiek')
                ->description('Besluiten 2, 5, 15 en 17.')
                ->columns(3)
                ->schema([
                    Select::make('settings.participation_model')->label('Deelnamemodel')->options(ParticipationModel::class)->required(),
                    Select::make('settings.logistics_mode')->label('Logistiek')->options(LogisticsMode::class)->required(),
                    Select::make('settings.finalist_fallback')->label('Provinciewinnaar kan niet naar finale')->options(FinalistFallback::class)->required(),
                    TextInput::make('settings.pieces_per_entry')->label('Stuks per inzending')->numeric()->minValue(1)->required(),
                    TextInput::make('settings.product_variant')->label('Productvariant')->required()
                        ->helperText('Bijvoorbeeld krenten_rozijnen of naturel.'),
                ]),
            Section::make('Cadeaubonnen')
                ->columns(3)
                ->schema([
                    TextInput::make('settings.voucher_count_per_winner')->label('Bonnen per Top 10-ondernemer')->numeric()->minValue(0)->required(),
                    TextInput::make('settings.voucher_value_cents')->label('Waarde per bon (centen)')->numeric()->minValue(0)->required()
                        ->helperText('4500 = EUR 45,00.'),
                    TextInput::make('settings.voucher_max_per_email')->label('Maximum bonnen per e-mailadres')->numeric()->minValue(1)->required(),
                ]),
            Section::make('Goede doelen')
                ->description('Besluit 8.')
                ->columns(2)
                ->schema([
                    TextInput::make('settings.charity_percentage')->label('Reserveringspercentage')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
                    Select::make('settings.charity_basis')->label('Grondslag')->options(CharityBasis::class)->required(),
                ]),
        ];
    }
}
