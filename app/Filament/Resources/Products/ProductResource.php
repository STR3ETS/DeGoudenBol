<?php

namespace App\Filament\Resources\Products;

use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Models\Product;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Sponsorcatalogus per editie (besluit 18).
 */
class ProductResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 41;

    protected static ?string $navigationLabel = 'Sponsorproducten';

    protected static ?string $modelLabel = 'sponsorproduct';

    protected static ?string $pluralModelLabel = 'sponsorcatalogus';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey())->required(),
                Select::make('code')->label('Soort')->options(ProductCode::class)->required(),
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                TextInput::make('price_cents')->label('Prijs excl. btw (euro)')->numeric()->required()->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('extra_link_price_cents')->label('Extra koppeling (euro)')->numeric()->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                TextInput::make('vat_rate')->label('Btw %')->numeric()->default(21)->required(),
                Select::make('available_from_phase')->label('Te koop')->options(AvailabilityPhase::class)->default(AvailabilityPhase::Always)->required(),
                Toggle::make('is_custom')->label('Maatwerk (prijs per plaatsing)'),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
                Textarea::make('description')->label('Omschrijving (openbaar op de sponsorpagina)')->rows(2)->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('Product')->weight('bold')->searchable(),
                TextColumn::make('code')->label('Soort'),
                TextColumn::make('price_cents')->label('Prijs')->formatStateUsing(fn (Product $record) => $record->is_custom ? 'op maat' : Money::format($record->price_cents)),
                TextColumn::make('extra_link_price_cents')->label('Extra')->formatStateUsing(fn (?int $state) => $state === null ? '–' : Money::format($state)),
                TextColumn::make('available_from_phase')->label('Te koop'),
                TextColumn::make('edition.name')->label('Editie'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }
}
