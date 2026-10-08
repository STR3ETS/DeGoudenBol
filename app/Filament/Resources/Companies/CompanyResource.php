<?php

namespace App\Filament\Resources\Companies;

use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Models\Company;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\RelationManagers\EntriesRelationManager;
use App\Filament\Resources\Companies\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Companies\RelationManagers\RecognitionsRelationManager;
use App\Filament\Resources\Companies\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Companies\RelationManagers\VoucherCampaignsRelationManager;
use App\Filament\Resources\Profiles\ProfileResource;
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

/**
 * Bedrijven met het bakkerdossier: één pagina per bedrijf met inschrijvingen, orders en facturen,
 * accounts, profieltekst, badges en cadeaubonnen als tabbladen.
 */
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
                Section::make('Bedrijf')->columns(4)->schema([
                    TextEntry::make('type')->label('Type')->badge(),
                    TextEntry::make('kvk_number')->label('KvK')->placeholder('–'),
                    TextEntry::make('founded_year')->label('Opgericht')->placeholder('–'),
                    TextEntry::make('website')->label('Website')->placeholder('–')->url(fn (Company $record) => $record->website, shouldOpenInNewTab: true),
                    TextEntry::make('contact_name')->label('Contact')->placeholder('–'),
                    TextEntry::make('contact_phone')->label('Telefoon')->placeholder('–'),
                    TextEntry::make('primaryLocation.city')->label('Plaats')->placeholder('–'),
                    TextEntry::make('primaryLocation.province.name')->label('Provincie')->placeholder('–'),
                ]),
                Section::make('Profieltekst')
                    ->description('Wat de bakker zelf schrijft voor de site, na keuring door Communicatie.')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('profile.moderation_status')->label('Status')->badge()->placeholder('Nog geen tekst'),
                        TextEntry::make('profile.tagline')->label('Korte omschrijving')->placeholder('–')->columnSpan(2),
                        TextEntry::make('profile.submitted_at')->label('Ingediend')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('profile.reviewer.name')->label('Beoordeeld door')->placeholder('–'),
                        TextEntry::make('profile.reviewed_at')->label('Beoordeeld op')->dateTime('d-m-Y H:i')->placeholder('–'),
                        TextEntry::make('moderation_link')
                            ->label('Keuren')
                            ->state('Naar profielteksten keuren')
                            ->url(fn () => ProfileResource::getUrl('index'))
                            ->visible(fn () => ProfileResource::canViewAny())
                            ->columnSpan(2),
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
            ->recordUrl(fn (Company $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Dossier'),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
            OrdersRelationManager::class,
            UsersRelationManager::class,
            RecognitionsRelationManager::class,
            VoucherCampaignsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }
}
