<?php

namespace App\Filament\Resources\Charities;

use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Models\Charity;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Charities\Pages\CreateCharity;
use App\Filament\Resources\Charities\Pages\EditCharity;
use App\Filament\Resources\Charities\Pages\ListCharities;
use App\Filament\Resources\Charities\RelationManagers\PayoutsRelationManager;
use App\Filament\Resources\Charities\RelationManagers\ReservationsRelationManager;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Goede doelen: voordrachten (portaal of backoffice), beoordeling met checklist, reserveringen en uitbetaling.
 */
class CharityResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Charity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Goede doelen';

    protected static ?string $modelLabel = 'goed doel';

    protected static ?string $pluralModelLabel = 'goede doelen';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance, StaffRole::Communication];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Goed doel')->columns(2)->schema([
                    TextInput::make('name')->label('Naam')->required()->maxLength(160),
                    TextInput::make('kvk_or_rsin')->label('KvK- of RSIN-nummer')->maxLength(20),
                    Select::make('province_id')->label('Regio')->options(fn () => Province::query()->orderBy('sort')->pluck('name', 'id')->all())->placeholder('Landelijk'),
                    Select::make('category')->label('Categorie')->options(config('charities.categories')),
                    TextInput::make('website')->label('Website')->url()->maxLength(255),
                    Toggle::make('is_anbi')->label('ANBI-status'),
                    Textarea::make('motivation')->label('Motivatie')->rows(3)->columnSpanFull(),
                ]),
                Section::make('Voordracht door sponsor')->description('Deelnemers dragen voor via het portaal; sponsoren via dit formulier.')->schema([
                    Select::make('nominated_by_id')->label('Sponsor')->options(fn () => Sponsor::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->required()->visibleOn('create'),
                ])->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['province', 'nominatedBy'])->withSum('reservations as reserved_cents', 'amount_cents')->withSum('payouts as paid_cents', 'amount_cents'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('Doel')->searchable()->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('province.name')->label('Regio')->placeholder('Landelijk'),
                TextColumn::make('nominatedBy.name')->label('Voorgedragen door')->placeholder('–'),
                TextColumn::make('reserved_cents')->label('Gereserveerd')->formatStateUsing(fn ($state) => Money::format((int) $state))->placeholder('€ 0,00'),
                TextColumn::make('paid_cents')->label('Uitbetaald')->formatStateUsing(fn ($state) => Money::format((int) $state))->placeholder('€ 0,00'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(CharityStatus::class),
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
            ])
            ->recordActions([
                EditAction::make()->label('Beoordelen'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ReservationsRelationManager::class,
            PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharities::route('/'),
            'create' => CreateCharity::route('/create'),
            'edit' => EditCharity::route('/{record}/edit'),
        ];
    }
}
