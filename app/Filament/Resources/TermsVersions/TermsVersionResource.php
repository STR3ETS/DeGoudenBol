<?php

namespace App\Filament\Resources\TermsVersions;

use App\Domain\Edition\Models\TermsVersion;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\TermsVersions\Pages\CreateTermsVersion;
use App\Filament\Resources\TermsVersions\Pages\EditTermsVersion;
use App\Filament\Resources\TermsVersions\Pages\ListTermsVersions;
use App\Filament\Resources\TermsVersions\Schemas\TermsVersionForm;
use App\Filament\Resources\TermsVersions\Tables\TermsVersionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TermsVersionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = TermsVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Editie';

    protected static ?int $navigationSort = 40;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'voorwaardenversie';

    protected static ?string $pluralModelLabel = 'voorwaarden';

    protected static ?string $navigationLabel = 'Voorwaarden en privacy';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TermsVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TermsVersionsTable::configure($table);
    }

    protected static function allowedRoles(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTermsVersions::route('/'),
            'create' => CreateTermsVersion::route('/create'),
            'edit' => EditTermsVersion::route('/{record}/edit'),
        ];
    }
}
