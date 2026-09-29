<?php

namespace App\Filament\Resources\Editions;

use App\Domain\Edition\Models\Edition;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Editions\Pages\CreateEdition;
use App\Filament\Resources\Editions\Pages\EditEdition;
use App\Filament\Resources\Editions\Pages\ListEditions;
use App\Filament\Resources\Editions\Pages\ViewEdition;
use App\Filament\Resources\Editions\RelationManagers\ProvincesRelationManager;
use App\Filament\Resources\Editions\Schemas\EditionForm;
use App\Filament\Resources\Editions\Schemas\EditionInfolist;
use App\Filament\Resources\Editions\Tables\EditionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EditionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Edition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Editie';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'editie';

    protected static ?string $pluralModelLabel = 'edities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EditionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EditionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EditionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProvincesRelationManager::class,
        ];
    }

    protected static function allowedRoles(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditions::route('/'),
            'create' => CreateEdition::route('/create'),
            'view' => ViewEdition::route('/{record}'),
            'edit' => EditEdition::route('/{record}/edit'),
        ];
    }
}
