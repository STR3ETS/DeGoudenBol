<?php

namespace App\Filament\Resources\Samples;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Samples\Pages\ListSamples;
use App\Filament\Resources\Samples\Pages\ReviewSample;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Scorecontrole: monsters met hun kaarten, vlaggen en (voorlopige) uitslag. Alleen testnummers.
 */
class SampleResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Sample::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Scorecontrole';

    protected static ?string $modelLabel = 'monster';

    protected static ?string $pluralModelLabel = 'monsters';

    protected static ?string $recordTitleAttribute = 'sample_number';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Reviewer, StaffRole::Coordinator, StaffRole::Publisher];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['intake', 'result', 'sessions'])->withCount(['validScorecards', 'scorecards']))
            ->defaultSort('sample_number')
            ->columns([
                TextColumn::make('sample_number')->label('Testnummer')->weight('bold')->searchable()->sortable()->placeholder('–'),
                TextColumn::make('round')->label('Ronde')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('sessions.0.name')->label('Sessie')->state(fn (Sample $record) => $record->sessions->first()?->displayName())->placeholder('–'),
                TextColumn::make('intake.freshness_expires_at')->label('Vers tot')->dateTime('d-m H:i')->placeholder('–')
                    ->color(fn (Sample $record) => $record->isFresh() ? 'success' : 'gray'),
                TextColumn::make('valid_scorecards_count')->label('Kaarten')
                    ->formatStateUsing(fn (Sample $record) => "{$record->valid_scorecards_count}".($record->scorecards_count > $record->valid_scorecards_count ? " (+{$record->scorecards_count} tot.)" : ''))
                    ->badge()
                    ->color(fn (Sample $record) => ($record->result?->flags['missing_cards'] ?? true) ? 'warning' : 'success'),
                TextColumn::make('result.total')->label('Cijfer')->formatStateUsing(fn (?float $state) => $state === null ? '–' : number_format($state, 1, ',', '.'))->weight('bold'),
                IconColumn::make('outliers')->label('Afwijkers')->state(fn (Sample $record) => count($record->result?->flags['outliers'] ?? []) > 0)->boolean()->trueIcon(Heroicon::OutlinedFlag)->falseIcon(Heroicon::OutlinedMinus)->trueColor('warning')->falseColor('gray'),
                TextColumn::make('result.status')->label('Uitslag')->badge()->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('round')->label('Ronde')->options(SampleRound::class),
                SelectFilter::make('status')->label('Status')->options(SampleStatus::class)->multiple(),
                Filter::make('needs_attention')->label('Vraagt aandacht')->query(fn (Builder $query) => $query->whereHas('result', fn (Builder $result) => $result->where('status', 'pending'))->has('scorecards')),
            ])
            ->recordActions([
                ViewAction::make()->label('Controleren'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSamples::route('/'),
            'view' => ReviewSample::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
