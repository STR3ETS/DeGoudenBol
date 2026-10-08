<?php

namespace App\Filament\Resources\PublicationBatches\Pages;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\ApproveBatch;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\SubmitBatch;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use App\Filament\Support\PublicationCockpit;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewPublicationBatch extends ViewRecord
{
    protected static string $resource = PublicationBatchResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->label();
    }

    /**
     * De cockpit (stappen, vier ogen, blokkades) boven de gegevens en de uitslagen.
     */
    public function content(Schema $schema): Schema
    {
        $schema = parent::content($schema);

        return $schema->components([
            View::make('filament.publication-batches.cockpit')
                ->viewData(fn (): array => ['cockpit' => new PublicationCockpit($this->getRecord(), auth()->user())]),
            ...$schema->getComponents(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Indienen ter goedkeuring')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalDescription('Na indienen moeten twee andere publicatiemedewerkers de batch goedkeuren. U kunt zelf niet goedkeuren.')
                ->visible(fn () => $this->isPublisher() && $this->getRecord()->status === BatchStatus::Draft)
                ->action(fn (SubmitBatch $submit) => $this->run(fn () => $submit($this->getRecord(), auth()->user()), 'Batch ingediend')),
            Action::make('approve')
                ->label('Goedkeuren')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Controleer naam, provincie, cijfer en weergave van iedere uitslag voordat u goedkeurt.')
                ->visible(fn () => $this->canApprove())
                ->action(fn (ApproveBatch $approve) => $this->run(fn () => $approve($this->getRecord(), auth()->user()), 'Goedkeuring vastgelegd')),
            Action::make('publish')
                ->label('Nu publiceren')
                ->icon(Heroicon::OutlinedMegaphone)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Normaal publiceert het systeem op het geplande moment. Nu publiceren zet de uitslagen direct online.')
                ->visible(fn () => $this->isPublisher() && $this->getRecord()->status === BatchStatus::Approved)
                ->action(fn (PublishBatch $publish) => $this->run(fn () => $publish($this->getRecord(), auth()->user()), 'Batch gepubliceerd')),
        ];
    }

    private function canApprove(): bool
    {
        /** @var PublicationBatch $batch */
        $batch = $this->getRecord();
        $userId = (int) auth()->id();

        return $this->isPublisher()
            && $batch->status === BatchStatus::PendingApproval
            && (int) $batch->submitted_by !== $userId
            && ! $batch->approvals()->where('user_id', $userId)->exists();
    }

    private function isPublisher(): bool
    {
        return auth()->user()?->hasRole(StaffRole::Publisher->value) ?? false;
    }

    private function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
