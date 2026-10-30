<?php

namespace App\Filament\Resources\ConfidentialReports;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\PublishPersonalReport;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\ConfidentialReports\Pages\ManageConfidentialReports;
use App\Support\DutchTime;
use App\Support\Portal\PersonalReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use UnitEnum;

/**
 * Persoonlijke rapporten (briefing 29 okt §7): voor iedere deelnemer, uiterlijk een vast aantal dagen
 * na de keuring in het portaal. Het concept komt uit de geanonimiseerde panelnotities; de redactie
 * maakt het af en geeft het vrij. Staat los van de landelijke ranking.
 */
class ConfidentialReportResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = ConfidentialReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Persoonlijke rapporten';

    protected static ?string $modelLabel = 'persoonlijk rapport';

    protected static ?string $pluralModelLabel = 'persoonlijke rapporten';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication, StaffRole::Reviewer, StaffRole::Publisher];
    }

    public static function getNavigationBadge(): ?string
    {
        $open = ConfidentialReport::query()->open()->whereHas('entry', fn (Builder $query) => $query->whereNotNull('linked_at'))->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return ConfidentialReport::query()->open()->where('due_at', '<', now()->addDay())->exists() ? 'danger' : 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rapport')->description('Het concept komt uit de geanonimiseerde panelnotities. Sterke punten voorop, daarna concrete verbeterpunten en de algemene indruk. De scores per onderdeel komen automatisch uit de uitslag.')->schema([
                    TextEntry::make('entry.public_name')->label('Deelnemer'),
                    TextEntry::make('scores')->label('Score')->state(fn (ConfidentialReport $record) => self::scoreSummary($record)),
                    Textarea::make('strengths')->label('Sterke punten')->rows(5),
                    Textarea::make('opportunities')->label('Verbeterpunten')->rows(5),
                    Textarea::make('general_impression')->label('Algemene indruk')->rows(3),
                    Textarea::make('course_suggestion')->label('Tip (optioneel)')->rows(2)->helperText('Een cursus pas noemen ná de landelijke uitslag.'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.province', 'editor']))
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('entry.public_name')->label('Deelnemer')->searchable()->weight('bold'),
                TextColumn::make('entry.province.name')->label('Provincie'),
                TextColumn::make('tested_on')->label('Gekeurd')->date('d-m')->placeholder('–'),
                TextColumn::make('due_at')->label('Uiterlijk')
                    ->formatStateUsing(fn ($state) => $state ? DutchTime::format($state, 'dd D MMM') : '–')
                    ->color(fn (ConfidentialReport $record) => $record->isOverdue() ? 'danger' : ($record->isPublished() ? 'gray' : 'warning'))
                    ->description(fn (ConfidentialReport $record) => $record->isOverdue() ? 'Te laat' : null),
                TextColumn::make('status')->label('Rapport')->badge(),
                TextColumn::make('published_at')->label('Vrijgegeven')->dateTime('d-m H:i')->placeholder('–'),
                TextColumn::make('editor.name')->label('Redactie')->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ReportStatus::class),
                Filter::make('te_laat')->label('Te laat')->query(fn (Builder $query) => $query->open()->where('due_at', '<', now())),
            ])
            ->recordActions([
                EditAction::make()->label('Redigeren')->mutateDataUsing(function (array $data): array {
                    $data['edited_by'] = auth()->id();

                    return $data;
                }),
                Action::make('vrijgeven')
                    ->label('Vrijgeven')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->visible(fn (ConfidentialReport $record) => ! $record->isPublished())
                    ->requiresConfirmation()
                    ->modalHeading('Rapport vrijgeven aan de deelnemer?')
                    ->modalDescription('Het rapport komt in het portaal en de eigenaren krijgen één mail met de link. De landelijke ranking blijft geheim.')
                    ->action(function (ConfidentialReport $record, PublishPersonalReport $publish): void {
                        try {
                            $publish($record, auth()->user());
                            Notification::make()->title('Rapport vrijgegeven en gemaild')->success()->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    private static function scoreSummary(ConfidentialReport $record): string
    {
        $snapshot = $record->entry?->publicationItems()->latest('id')->value('result_snapshot');
        $snapshot = is_string($snapshot) ? json_decode($snapshot, true) : $snapshot;

        return isset($snapshot['total_raw'])
            ? PersonalReport::number(round((float) $snapshot['total_raw'], 1)).' van 100 punten · '.($snapshot['card_count'] ?? '–').' geldige kaarten'
            : 'Nog geen gekoppelde uitslag';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConfidentialReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
