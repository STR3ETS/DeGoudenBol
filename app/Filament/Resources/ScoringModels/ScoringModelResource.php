<?php

namespace App\Filament\Resources\ScoringModels;

use App\Domain\Edition\Models\ScoringModel;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\ScoringModels\Pages\CreateScoringModel;
use App\Filament\Resources\ScoringModels\Pages\EditScoringModel;
use App\Filament\Resources\ScoringModels\Pages\ListScoringModels;
use App\Filament\Resources\ScoringModels\RelationManagers\CriteriaRelationManager;
use App\Filament\Resources\ScoringModels\Schemas\ScoringModelForm;
use App\Filament\Resources\ScoringModels\Tables\ScoringModelsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScoringModelResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = ScoringModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Editie';

    protected static ?int $navigationSort = 30;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'beoordelingsmodel';

    protected static ?string $pluralModelLabel = 'beoordelingsmodellen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ScoringModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScoringModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CriteriaRelationManager::class,
        ];
    }

    protected static function allowedRoles(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScoringModels::route('/'),
            'create' => CreateScoringModel::route('/create'),
            'edit' => EditScoringModel::route('/{record}/edit'),
        ];
    }
}
