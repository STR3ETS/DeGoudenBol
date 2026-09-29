<?php

namespace App\Filament\Resources\TieBreakRounds;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\TieBreakRounds\Pages\ManageTieBreakRounds;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Beslisrondes: gedeelde plaats 1 of gedeelde grens van de Top 10. De testcoördinatie plant nieuwe
 * monsters (B01…) via ontvangst; de uitkomst verschijnt hier zodra alle betrokken monsters definitief zijn.
 */
class TieBreakRoundResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = TieBreakRound::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Beslisrondes';

    protected static ?string $modelLabel = 'beslisronde';

    protected static ?string $pluralModelLabel = 'beslisrondes';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Publisher, StaffRole::Coordinator, StaffRole::Reviewer];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('province'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('province.name')->label('Provincie'),
                TextColumn::make('scope')->label('Waar')->formatStateUsing(fn (TieBreakRound $record) => $record->scopeLabel()),
                TextColumn::make('entry_ids')->label('Deelnemers')->formatStateUsing(fn (TieBreakRound $record) => Entry::query()->whereIn('id', $record->entry_ids)->pluck('public_name')->implode(' · ')),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('scores')->label('Gescoord')->formatStateUsing(fn (TieBreakRound $record) => count($record->scores ?? []).' / '.count($record->entry_ids)),
                TextColumn::make('outcome_order')->label('Uitkomst')->formatStateUsing(function (TieBreakRound $record): string {
                    if (! $record->outcome_order) {
                        return '–';
                    }

                    $names = Entry::query()->whereIn('id', $record->outcome_order)->pluck('public_name', 'id');

                    return collect($record->outcome_order)->map(fn (int $id, int $i) => ($i + 1).'. '.($names[$id] ?? $id))->implode(' · ');
                }),
                TextColumn::make('decided_at')->label('Beslist')->dateTime('d-m H:i')->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('status')->label('Status')->options(TieBreakStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTieBreakRounds::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
