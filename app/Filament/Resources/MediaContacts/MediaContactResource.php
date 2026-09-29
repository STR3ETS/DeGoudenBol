<?php

namespace App\Filament\Resources\MediaContacts;

use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\MediaContacts\Pages\ManageMediaContacts;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MediaContactResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = MediaContact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 41;

    protected static ?string $navigationLabel = 'Mediacontacten';

    protected static ?string $modelLabel = 'mediacontact';

    protected static ?string $pluralModelLabel = 'mediacontacten';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                TextInput::make('outlet')->label('Medium')->required()->maxLength(120),
                TextInput::make('email')->label('E-mailadres')->email()->required()->unique(ignoreRecord: true)->maxLength(190),
                Select::make('province_id')->label('Provincie')->options(fn () => Province::query()->orderBy('sort')->pluck('name', 'id')->all())->placeholder('Landelijk')->helperText('Leeg = landelijk medium: ontvangt alle perskits.'),
                Textarea::make('notes')->label('Notities')->rows(2)->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('province'))
            ->defaultSort('outlet')
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->weight('bold'),
                TextColumn::make('outlet')->label('Medium')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('province.name')->label('Provincie')->placeholder('Landelijk')->sortable(),
            ])
            ->filters([
                SelectFilter::make('province_id')->label('Provincie')->options(fn () => Province::query()->orderBy('sort')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediaContacts::route('/'),
        ];
    }
}
