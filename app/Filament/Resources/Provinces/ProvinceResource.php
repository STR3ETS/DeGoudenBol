<?php

namespace App\Filament\Resources\Provinces;

use App\Domain\Edition\Models\Province;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Provinces\Pages\ManageProvinces;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * De twaalf provincies zijn vast: alleen naam en volgorde zijn aan te passen.
 */
class ProvinceResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Province::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Editie';

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'provincie';

    protected static ?string $pluralModelLabel = 'provincies';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(64),
                TextInput::make('slug')->label('Slug (URL)')->required()->alphaDash()->unique(ignoreRecord: true),
                TextInput::make('code')->label('Code')->required()->length(2),
                TextInput::make('sort')->label('Volgorde')->numeric()->required()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('sort')->label('#')->sortable(),
                TextColumn::make('name')->label('Provincie')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('code')->label('Code'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function allowedRoles(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProvinces::route('/'),
        ];
    }
}
