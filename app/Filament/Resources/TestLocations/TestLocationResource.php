<?php

namespace App\Filament\Resources\TestLocations;

use App\Domain\Edition\Models\TestLocation;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\TestLocations\Pages\ManageTestLocations;
use BackedEnum;
use Filament\Actions\EditAction;
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

class TestLocationResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = TestLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Testketen';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'testlocatie';

    protected static ?string $pluralModelLabel = 'testlocaties';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('street')->label('Straat en huisnummer')->maxLength(255),
                TextInput::make('postcode')->label('Postcode')->maxLength(10),
                TextInput::make('city')->label('Plaats')->maxLength(255),
                TextInput::make('capacity')->label('Capaciteit (monsters per dag)')->numeric()->minValue(1),
                Textarea::make('notes')->label('Notities')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Actief')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable(),
                TextColumn::make('city')->label('Plaats')->searchable(),
                TextColumn::make('capacity')->label('Capaciteit')->placeholder('–'),
                IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function allowedRoles(): array
    {
        return [StaffRole::Intake, StaffRole::Coordinator];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTestLocations::route('/'),
        ];
    }
}
