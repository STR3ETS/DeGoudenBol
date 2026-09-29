<?php

namespace App\Filament\Resources\TestSessions;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TestLocation;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\TestSession;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\TestSessions\Pages\CreateTestSession;
use App\Filament\Resources\TestSessions\Pages\EditTestSession;
use App\Filament\Resources\TestSessions\Pages\ListTestSessions;
use App\Filament\Resources\TestSessions\Pages\ViewTestSession;
use App\Filament\Resources\TestSessions\RelationManagers\PanelistsRelationManager;
use App\Filament\Resources\TestSessions\RelationManagers\SamplesRelationManager;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Testsessies: monsters en panelleden koppelen, uitserveerschema genereren, versheid bewaken.
 */
class TestSessionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = TestSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Testsessies';

    protected static ?string $modelLabel = 'testsessie';

    protected static ?string $pluralModelLabel = 'testsessies';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Coordinator, StaffRole::Reviewer];
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
                Select::make('round')->label('Ronde')->options(SampleRound::class)->default(SampleRound::Provincial)->required(),
                TextInput::make('name')->label('Naam')->maxLength(80)->placeholder('Bijv. Sessie A – ochtend'),
                Select::make('test_location_id')->label('Testlocatie')
                    ->options(fn () => TestLocation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn () => TestLocation::query()->where('is_active', true)->value('id')),
                DateTimePicker::make('starts_at')->label('Begin')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label('Einde')->seconds(false)->required()->after('starts_at'),
                TextInput::make('max_samples')->label('Maximaal aantal monsters')->numeric()->minValue(1)
                    ->default(fn () => Edition::current()?->settings->maxSamplesPerSession ?? 8)->required(),
                Select::make('status')->label('Status')->options(SessionStatus::class)->default(SessionStatus::Planned)->required(),
                Textarea::make('notes')->label('Notities')->rows(2)->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sessie')->columns(4)->schema([
                    TextEntry::make('starts_at')->label('Begin')->dateTime('D d-m-Y H:i'),
                    TextEntry::make('ends_at')->label('Einde')->dateTime('H:i'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('round')->label('Ronde'),
                    TextEntry::make('test_location_id')->label('Locatie')->formatStateUsing(fn (?int $state) => $state ? TestLocation::query()->whereKey($state)->value('name') : '–'),
                    TextEntry::make('max_samples')->label('Max. monsters'),
                    TextEntry::make('schedule_generated_at')->label('Schema gegenereerd')->dateTime('d-m-Y H:i')->placeholder('Nog niet'),
                    TextEntry::make('notes')->label('Notities')->placeholder('–'),
                ]),
                View::make('filament.sessions.schedule')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['samples', 'panelists', 'scorecards']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Sessie')->formatStateUsing(fn (TestSession $record) => $record->displayName())->searchable(),
                TextColumn::make('starts_at')->label('Begin')->dateTime('D d-m-Y H:i')->sortable(),
                TextColumn::make('round')->label('Ronde')->toggleable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('samples_count')->label('Monsters')->formatStateUsing(fn (TestSession $record) => "{$record->samples_count} / {$record->max_samples}"),
                TextColumn::make('panelists_count')->label('Panel'),
                TextColumn::make('scorecards_count')->label('Kaarten'),
                TextColumn::make('schedule_generated_at')->label('Schema')->dateTime('d-m H:i')->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('status')->label('Status')->options(SessionStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SamplesRelationManager::class,
            PanelistsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestSessions::route('/'),
            'create' => CreateTestSession::route('/create'),
            'view' => ViewTestSession::route('/{record}'),
            'edit' => EditTestSession::route('/{record}/edit'),
        ];
    }
}
