<?php

namespace App\Filament\Resources\Sponsors;

use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Sponsors\Pages\CreateSponsor;
use App\Filament\Resources\Sponsors\Pages\EditSponsor;
use App\Filament\Resources\Sponsors\Pages\ListSponsors;
use App\Filament\Resources\Sponsors\RelationManagers\PlacementsRelationManager;
use App\Filament\Resources\Sponsors\RelationManagers\SponsorLinksRelationManager;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
 * Sponsoren met plaatsingen (locatie, periode, exclusiviteit) en "Bakt met"-koppelingen.
 * Sponsoren zien nooit testgegevens; de facturatie loopt via de gewone orders.
 */
class SponsorResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Sponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Sponsoren';

    protected static ?string $modelLabel = 'sponsor';

    protected static ?string $pluralModelLabel = 'sponsoren';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance, StaffRole::Communication];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sponsor')->columns(2)->schema([
                    TextInput::make('name')->label('Naam')->required()->maxLength(160),
                    TextInput::make('url')->label('Website')->url()->maxLength(255),
                    FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('sponsors')->visibility('public')->maxSize(2048)->helperText('PNG of SVG met transparante achtergrond werkt het best.'),
                    Select::make('status')->label('Status')->options(SponsorStatus::class)->default(SponsorStatus::Active)->required(),
                ]),
                Section::make('Contact en facturatie')->columns(2)->schema([
                    TextInput::make('contact_name')->label('Contactpersoon')->maxLength(120),
                    TextInput::make('contact_email')->label('E-mail')->email()->maxLength(190),
                    TextInput::make('contact_phone')->label('Telefoon')->maxLength(40),
                    TextInput::make('kvk_number')->label('KvK-nummer')->maxLength(20),
                    TextInput::make('billing_address.street')->label('Straat en huisnummer')->maxLength(160),
                    TextInput::make('billing_address.postcode')->label('Postcode')->maxLength(10),
                    TextInput::make('billing_address.city')->label('Plaats')->maxLength(100),
                    Textarea::make('notes')->label('Notities')->rows(2)->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['placements', 'links']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Sponsor')->searchable()->sortable()->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('placements_count')->label('Plaatsingen'),
                TextColumn::make('links_count')->label('Koppelingen'),
                TextColumn::make('contact_email')->label('Contact')->placeholder('–')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(SponsorStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PlacementsRelationManager::class,
            SponsorLinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSponsors::route('/'),
            'create' => CreateSponsor::route('/create'),
            'edit' => EditSponsor::route('/{record}/edit'),
        ];
    }
}
