<?php

namespace App\Filament\Resources\Panelists;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Models\Panelist;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Panelists\Pages\ManagePanelists;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Panelpool: welke medewerkers met de rol panellid meedoen, onder welke code, met welke allergenen.
 */
class PanelistResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Panelist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Panelleden';

    protected static ?string $modelLabel = 'panellid';

    protected static ?string $pluralModelLabel = 'panelleden';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Coordinator];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')->label('Medewerker (rol panellid)')
                    ->options(fn () => User::role(StaffRole::Panelist->value)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Alleen accounts met de rol panellid; ken die rol toe bij Medewerkers.'),
                TextInput::make('display_code')->label('Weergavecode')
                    ->default(fn () => Panelist::nextDisplayCode())
                    ->required()->maxLength(8)->unique(ignoreRecord: true)
                    ->helperText('Zo heet dit panellid in de app en op kaarten (P07). Nooit een naam.'),
                Select::make('edition_id')->label('Editie')
                    ->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())
                    ->default(fn () => Edition::current()?->getKey()),
                Select::make('pool_status')->label('Status')->options(PoolStatus::class)->default(PoolStatus::Active)->required(),
                TextInput::make('fee_per_session_cents')->label('Vergoeding per sessie (centen)')->numeric()->minValue(0)->default(0),
                DateTimePicker::make('consent_at')->label('Toestemming gezondheidsgegevens')->seconds(false)
                    ->helperText('Allergenen zijn gezondheidsgegevens: alleen met toestemming en na de editie gewist.'),
                CheckboxList::make('allergens')->label('Allergenen van het panellid')->options(Allergen::options())->columns(2)->columnSpanFull(),
                Textarea::make('notes')->label('Notities')->rows(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_code')
            ->columns([
                TextColumn::make('display_code')->label('Code')->weight('bold')->searchable(),
                TextColumn::make('user_id')->label('Medewerker')->formatStateUsing(fn (int $state) => User::query()->whereKey($state)->value('name') ?? '–'),
                TextColumn::make('pool_status')->label('Status')->badge(),
                TextColumn::make('allergens')->label('Allergenen')->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)->badge()->color('gray'),
                TextColumn::make('consent_at')->label('Toestemming')->dateTime('d-m-Y')->placeholder('–'),
                TextColumn::make('fee_per_session_cents')->label('Vergoeding')->money('EUR', divideBy: 100),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePanelists::route('/'),
        ];
    }
}
