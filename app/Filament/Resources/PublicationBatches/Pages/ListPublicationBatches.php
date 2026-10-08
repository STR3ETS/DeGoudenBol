<?php

namespace App\Filament\Resources\PublicationBatches\Pages;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\FreezeEdition;
use App\Domain\Ranking\Actions\RevealProvinces;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Publicatiebatches plus de twee grote momenten van de editie: bevriezen en onthullen.
 */
class ListPublicationBatches extends ListRecords
{
    protected static string $resource = PublicationBatchResource::class;

    /**
     * Werkvoorraad: eerst de batches die nog iets van Publicatie vragen.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $open = [BatchStatus::Draft, BatchStatus::PendingApproval, BatchStatus::Approved];
        $count = function (array $statuses): ?int {
            $edition = Edition::current();
            $count = $edition === null ? 0 : PublicationBatch::query()->where('edition_id', $edition->getKey())->whereIn('status', $statuses)->count();

            return $count > 0 ? $count : null;
        };

        return [
            'open' => Tab::make('Open')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $open))->badge(fn (): ?int => $count($open)),
            'goedkeuren' => Tab::make('Wacht op goedkeuring')->modifyQueryUsing(fn (Builder $query) => $query->where('status', BatchStatus::PendingApproval))->badge(fn (): ?int => $count([BatchStatus::PendingApproval])),
            'gepubliceerd' => Tab::make('Gepubliceerd')->modifyQueryUsing(fn (Builder $query) => $query->where('status', BatchStatus::Published))->badge(fn (): ?int => $count([BatchStatus::Published])),
            'alle' => Tab::make('Alle'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }

    protected function getHeaderActions(): array
    {
        $edition = Edition::current();

        return [
            Action::make('freeze')
                ->label('Voorlijsten bevriezen')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Voorlijsten '.($edition?->year ?? '').' bevriezen')
                ->modalDescription(function () use ($edition): string {
                    $open = $edition ? TieBreakRound::query()->where('edition_id', $edition->getKey())->where('status', TieBreakStatus::Open)->count() : 0;

                    return 'In één transactie: definitieve Top 10 per provincie, provinciewinnaars en finale-inschrijvingen. De lijsten blijven onder embargo tot de reveal per provincie op de publicatiedag.'
                        .($open > 0 ? " Let op: er staan nog {$open} beslisronde(s) open; de bevriezing wordt dan geweigerd." : '');
                })
                ->visible(fn () => $this->isPublisher() && $edition !== null && in_array($edition->status, [EditionStatus::Testing, EditionStatus::Closed], true))
                ->action(fn (FreezeEdition $freeze) => $this->run(fn () => $freeze($edition, auth()->user()), 'Voorlijsten bevroren; finalisten zijn uitgenodigd')),
            Action::make('reveal')
                ->label('Provincies nu onthullen')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Normaal onthult het systeem iedere provincie op het ingestelde reveal-moment. Deze knop onthult nu alle provincies waarvan het moment al verstreken is.')
                ->visible(fn () => $this->isPublisher() && $edition?->status === EditionStatus::Frozen)
                ->action(fn (RevealProvinces $reveal) => $this->run(function () use ($reveal, $edition): string {
                    $names = $reveal($edition);

                    return $names === [] ? 'Geen provincie waarvan het reveal-moment al verstreken is.' : 'Onthuld: '.implode(', ', $names);
                })),
        ];
    }

    private function isPublisher(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::Publisher->value, StaffRole::Admin->value]) ?? false;
    }

    private function run(callable $callback, ?string $successTitle = null): void
    {
        try {
            $message = $callback();
            Notification::make()->title($successTitle ?? (is_string($message) ? $message : 'Verwerkt'))->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
