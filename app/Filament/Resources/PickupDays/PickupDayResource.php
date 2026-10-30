<?php

namespace App\Filament\Resources\PickupDays;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\PickupDays\Pages\ManagePickupDays;
use App\Support\DutchTime;
use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Vrijdagen voor de ophaalservice (briefing 29 okt §5), met capaciteit. Alleen vrijdagen.
 */
class PickupDayResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = PickupDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Ophaalvrijdagen';

    protected static ?string $modelLabel = 'ophaalvrijdag';

    protected static ?string $pluralModelLabel = 'ophaalvrijdagen';

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
                DatePicker::make('date')->label('Vrijdag')->required()
                    ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                        if (CarbonImmutable::parse((string) $value)->dayOfWeekIso !== 5) {
                            $fail('De ophaalservice rijdt alleen op vrijdag.');
                        }
                    }),
                TextInput::make('capacity')->label('Maximaal aantal adressen')->numeric()->minValue(1)->default(10)->required(),
                Toggle::make('is_open')->label('Open voor aanvragen')->default(true)->inline(false),
                TextInput::make('notes')->label('Notitie')->maxLength(500)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['requests as taken_count' => fn (Builder $requests) => $requests->whereIn('status', PickupStatus::occupying())]))
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label('Vrijdag')->formatStateUsing(fn ($state) => ucfirst(DutchTime::format($state, 'dddd D MMMM YYYY')))->sortable(),
                TextColumn::make('taken_count')->label('Aanvragen')->formatStateUsing(fn (PickupDay $record) => "{$record->taken_count} / {$record->capacity}")->badge()
                    ->color(fn (PickupDay $record) => $record->taken_count >= $record->capacity ? 'danger' : 'success'),
                IconColumn::make('is_open')->label('Open')->boolean(),
                TextColumn::make('notes')->label('Notitie')->placeholder('–')->limit(40),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (PickupDay $record) => $record->taken_count === 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePickupDays::route('/'),
        ];
    }
}
