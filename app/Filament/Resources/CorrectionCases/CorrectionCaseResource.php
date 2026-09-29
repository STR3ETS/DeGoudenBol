<?php

namespace App\Filament\Resources\CorrectionCases;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\ApproveCorrectionCase;
use App\Domain\Ranking\Actions\OpenCorrectionCase;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Domain\Ranking\Models\CorrectionCase;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\CorrectionCases\Pages\ManageCorrectionCases;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use UnitEnum;

/**
 * Correctiedossiers na publicatie: twee goedkeuringen, nieuwe uitslag in de volgende batch.
 */
class CorrectionCaseResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = CorrectionCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Correcties na publicatie';

    protected static ?string $modelLabel = 'correctiedossier';

    protected static ?string $pluralModelLabel = 'correctiedossiers';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Publisher, StaffRole::Reviewer];
    }

    /**
     * Aantal correctiedossiers dat op de tweede goedkeurder wacht, als pil in het menu.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = CorrectionCase::query()->where('status', CorrectionCaseStatus::PendingApproval)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Wacht op goedkeuring';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.province', 'submitter'])->withCount('approvals'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('entry.public_name')->label('Deelnemer')->weight('bold')->searchable(),
                TextColumn::make('entry.province.name')->label('Provincie'),
                TextColumn::make('original_snapshot.total')->label('Was')->formatStateUsing(fn ($state) => number_format((float) $state, 1, ',', '.')),
                TextColumn::make('new_snapshot.total')->label('Wordt')->formatStateUsing(fn ($state) => $state === null ? '–' : number_format((float) $state, 1, ',', '.'))->placeholder('–'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('approvals_count')->label('Goedkeuringen')->formatStateUsing(fn (int $state) => "{$state} / ".CorrectionCase::REQUIRED_APPROVALS),
                TextColumn::make('submitter.name')->label('Ingediend door')->placeholder('–'),
                TextColumn::make('reason')->label('Reden')->limit(50)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(CorrectionCaseStatus::class),
            ])
            ->headerActions([
                Action::make('open')
                    ->label('Dossier openen')
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn () => auth()->user()?->hasAnyRole([StaffRole::Publisher->value, StaffRole::Reviewer->value]) ?? false)
                    ->schema([
                        Select::make('entry_id')->label('Gepubliceerde deelnemer')
                            ->options(fn () => Entry::query()->where('edition_id', Edition::current()?->getKey())->whereIn('status', [EntryStatus::Published, EntryStatus::Confidential])->orderBy('public_name')->pluck('public_name', 'id')->all())
                            ->searchable()->required(),
                        Textarea::make('reason')->label('Reden (aantoonbare fout in de procedure of invoer)')->required()->rows(3),
                    ])
                    ->action(function (array $data, OpenCorrectionCase $open): void {
                        self::run(fn () => $open(Entry::query()->findOrFail($data['entry_id']), $data['reason'], auth()->user()), 'Dossier geopend; twee publicatiemedewerkers moeten goedkeuren');
                    }),
            ])
            ->recordActions([
                Action::make('approve')->label('Goedkeuren')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Bij de tweede goedkeuring wordt de uitslag opnieuw berekend en in de eerstvolgende batch gezet.')
                    ->visible(fn (CorrectionCase $record) => $record->status === CorrectionCaseStatus::PendingApproval && (int) $record->submitted_by !== (int) auth()->id() && (auth()->user()?->hasRole(StaffRole::Publisher->value) ?? false))
                    ->action(fn (CorrectionCase $record, ApproveCorrectionCase $approve) => self::run(fn () => $approve($record, auth()->user()), 'Goedkeuring vastgelegd')),
                Action::make('reject')->label('Afwijzen')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CorrectionCase $record) => $record->status === CorrectionCaseStatus::PendingApproval && (auth()->user()?->hasRole(StaffRole::Publisher->value) ?? false))
                    ->action(fn (CorrectionCase $record, ApproveCorrectionCase $approve) => self::run(fn () => $approve($record, auth()->user(), approve: false), 'Dossier afgewezen')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCorrectionCases::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
