<?php

namespace App\Filament\Resources\Packages;

use App\Domain\Commerce\Models\Package;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Packages\Pages\ManagePackages;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PackageResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Pakketten en prijzen';

    protected static ?string $modelLabel = 'deelnamepakket';

    protected static ?string $pluralModelLabel = 'deelnamepakketten';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('edition_id')->label('Editie')->relationship('edition', 'name')->required(),
                TextInput::make('code')->label('Code')->required()->alphaDash()->maxLength(64)->helperText('Vaste sleutel, bijvoorbeeld deelname-2026-basis.'),
                TextInput::make('name')->label('Naam')->required()->maxLength(120)->helperText('Nooit een rangnummer in de naam (besluit 2).'),
                TextInput::make('price_euros')
                    ->label('Prijs excl. btw (EUR)')
                    ->numeric()
                    ->step(0.01)
                    ->required()
                    ->formatStateUsing(fn (?Package $record) => $record ? number_format($record->price_cents / 100, 2, '.', '') : null)
                    ->dehydrated(false)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('price_cents', Money::fromEuros((string) $state)))
                    ->live(onBlur: true),
                TextInput::make('price_cents')->label('Prijs in centen')->numeric()->required()->helperText('Wordt automatisch gevuld vanuit het eurobedrag.'),
                TextInput::make('vat_rate')->label('Btw-tarief (%)')->numeric()->step(0.01)->default(21)->required(),
                TextInput::make('stock_per_province')->label('Voorraad per provincie')->numeric()->minValue(1)->helperText('Leeg = onbeperkt (model B). Alleen bij model A.'),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
                Toggle::make('is_base')->label('Basispakket')->inline(false),
                Toggle::make('is_active')->label('Actief')->default(true)->inline(false),
                Textarea::make('description')->label('Omschrijving (op de site en de factuur)')->rows(3)->columnSpanFull(),
                TagsInput::make('entitlements')->label('Rechtenmatrix (marketingdiensten)')->placeholder('code toevoegen…')->columnSpanFull()
                    ->helperText('Bijvoorbeeld badge_participant, social_kit, confidential_report, press_release_region.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('edition.year')->label('Editie')->sortable(),
                TextColumn::make('name')->label('Naam')->searchable(),
                TextColumn::make('code')->label('Code')->fontFamily('mono'),
                TextColumn::make('price_cents')->label('Prijs excl.')->formatStateUsing(fn (int $state) => Money::format($state))->alignRight(),
                TextColumn::make('vat_rate')->label('Btw')->suffix('%'),
                TextColumn::make('stock_per_province')->label('Voorraad/prov.')->placeholder('onbeperkt'),
                IconColumn::make('is_base')->label('Basis')->boolean(),
                IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePackages::route('/'),
        ];
    }
}
