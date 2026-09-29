<?php

namespace App\Filament\Resources\Companies;

use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Models\Company;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompanyResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Bakkers';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Bedrijven';

    protected static ?string $modelLabel = 'bedrijf';

    protected static ?string $pluralModelLabel = 'bedrijven';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Zoekbaar op naam en KvK-nummer in het zoekpalet (Ctrl+K).
     *
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'kvk_number'];
    }

    protected static function allowedRoles(): array
    {
        return [StaffRole::Intake, StaffRole::Communication, StaffRole::Finance, StaffRole::VoucherManager];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                Select::make('type')->label('Type')->options(CompanyType::class)->required(),
                TextInput::make('kvk_number')->label('KvK-nummer')->length(8)->nullable(),
                TextInput::make('founded_year')->label('Opgericht in')->numeric()->minValue(1800)->maxValue((int) now()->year),
                TextInput::make('website')->label('Website')->url()->maxLength(190),
                TextInput::make('contact_name')->label('Contactpersoon')->maxLength(120),
                TextInput::make('contact_phone')->label('Telefoon')->maxLength(32),
                Toggle::make('is_archived')->label('Gearchiveerd')->inline(false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bedrijf')->columns(3)->schema([
                    TextEntry::make('name')->label('Naam'),
                    TextEntry::make('type')->label('Type')->badge(),
                    TextEntry::make('kvk_number')->label('KvK')->placeholder('–'),
                    TextEntry::make('slug')->label('Profiel-URL')->formatStateUsing(fn (string $state) => route('bakkers.toon', $state))->url(fn (Company $record) => route('bakkers.toon', $record), shouldOpenInNewTab: true),
                    TextEntry::make('website')->label('Website')->placeholder('–'),
                    TextEntry::make('contact_name')->label('Contact')->placeholder('–'),
                    TextEntry::make('contact_phone')->label('Telefoon')->placeholder('–'),
                    TextEntry::make('primaryLocation.city')->label('Plaats')->placeholder('–'),
                    TextEntry::make('primaryLocation.province.name')->label('Provincie')->placeholder('–'),
                ]),
                Section::make('Accounts')->schema([
                    TextEntry::make('users.email')->label('E-mailadressen')->listWithLineBreaks()->placeholder('–'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['primaryLocation.province'])->withCount(['entries', 'users']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Bedrijf')->searchable()->sortable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('kvk_number')->label('KvK')->searchable()->placeholder('–'),
                TextColumn::make('primaryLocation.city')->label('Plaats')->searchable(),
                TextColumn::make('primaryLocation.province.name')->label('Provincie'),
                TextColumn::make('entries_count')->label('Inschrijvingen'),
                TextColumn::make('users_count')->label('Accounts'),
                IconColumn::make('is_archived')->label('Archief')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(CompanyType::options()),
                TernaryFilter::make('is_archived')->label('Gearchiveerd'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCompanies::route('/'),
        ];
    }
}
