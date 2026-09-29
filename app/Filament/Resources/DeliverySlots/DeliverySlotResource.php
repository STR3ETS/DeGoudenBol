<?php

namespace App\Filament\Resources\DeliverySlots;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TestLocation;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\DeliverySlots\Pages\ManageDeliverySlots;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class DeliverySlotResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = DeliverySlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Aanleverslots';

    protected static ?string $modelLabel = 'aanleverslot';

    protected static ?string $pluralModelLabel = 'aanleverslots';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Coordinator, StaffRole::Intake];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('edition_id')->label('Editie')
                    ->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())
                    ->default(fn () => Edition::current()?->getKey())
                    ->required(),
                Select::make('test_location_id')->label('Testlocatie')
                    ->options(fn () => TestLocation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn () => TestLocation::query()->where('is_active', true)->value('id')),
                DateTimePicker::make('starts_at')->label('Begin')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label('Einde')->seconds(false)->required()->after('starts_at'),
                TextInput::make('capacity')->label('Capaciteit (monsters)')->numeric()->minValue(1)->default(10)->required(),
                Textarea::make('notes')->label('Notities')->rows(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['testLocation', 'edition'])->withCount(['entries as taken_count' => fn (Builder $entries) => $entries->occupyingPlace()]))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label('Begin')->dateTime('D d-m-Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('Einde')->dateTime('H:i'),
                TextColumn::make('testLocation.name')->label('Locatie')->placeholder('–'),
                TextColumn::make('taken_count')->label('Bezet')->formatStateUsing(fn (DeliverySlot $record) => "{$record->taken_count} / {$record->capacity}")->badge()
                    ->color(fn (DeliverySlot $record) => $record->taken_count >= $record->capacity ? 'danger' : 'success'),
                TextColumn::make('edition.year')->label('Editie')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (DeliverySlot $record) => $record->taken_count === 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeliverySlots::route('/'),
        ];
    }
}
