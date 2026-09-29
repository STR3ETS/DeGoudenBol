<?php

namespace App\Filament\Resources\Objections;

use App\Domain\Participants\Enums\ObjectionStatus;
use App\Domain\Participants\Models\Objection;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Objections\Pages\ManageObjections;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Bezwaren (besluit 7): alleen over de procedure, beslist door mensen buiten panel en registratie.
 */
class ObjectionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Objection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Bezwaren';

    protected static ?string $modelLabel = 'bezwaar';

    protected static ?string $pluralModelLabel = 'bezwaren';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Publisher];
    }

    /**
     * Aantal bezwaren dat nog niet is afgehandeld, als pil in het menu.
     */
    public static function getNavigationBadge(): ?string
    {
        $open = Objection::query()->whereIn('status', [ObjectionStatus::Submitted, ObjectionStatus::Reviewing])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Nog af te handelen';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('entry.public_name')->label('Deelnemer'),
                TextEntry::make('reason')->label('Bezwaar'),
                Select::make('status')->label('Status')->options(ObjectionStatus::class)->required(),
                Textarea::make('decision')->label('Beslissing en motivering')->rows(4)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.province', 'decider']))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('entry.public_name')->label('Deelnemer')->weight('bold')->searchable(),
                TextColumn::make('entry.province.name')->label('Provincie'),
                TextColumn::make('submitted_at')->label('Ingediend')->dateTime('d-m-Y H:i'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('decider.name')->label('Beslist door')->placeholder('–'),
                TextColumn::make('reason')->label('Bezwaar')->limit(60)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ObjectionStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label('Behandelen')->mutateDataUsing(function (array $data): array {
                    $decided = in_array($data['status'] ?? null, [ObjectionStatus::Upheld->value, ObjectionStatus::Dismissed->value], true);
                    $data['decided_by'] = $decided ? auth()->id() : null;
                    $data['decided_at'] = $decided ? now() : null;

                    return $data;
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageObjections::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
