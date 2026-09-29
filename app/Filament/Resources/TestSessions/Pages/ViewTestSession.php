<?php

namespace App\Filament\Resources\TestSessions\Pages;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Jobs\GenerateServingScheduleJob;
use App\Domain\Testing\Models\TestSession;
use App\Filament\Resources\TestSessions\TestSessionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewTestSession extends ViewRecord
{
    protected static string $resource = TestSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSchedule')
                ->label(fn () => $this->getRecord()->schedule_generated_at ? 'Schema opnieuw genereren' : 'Uitserveerschema genereren')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Uitserveerschema genereren')
                ->modalDescription('Een systeemproces met kluistoegang sluit panelleden uit bij belangenconflicten en allergenen. Het schema toont alleen testnummers en panelcodes.')
                ->visible(fn () => $this->canCoordinate() && $this->getRecord()->status->acceptsScorecards() && ! $this->getRecord()->scorecards()->exists())
                ->action(function (): void {
                    /** @var TestSession $session */
                    $session = $this->getRecord();

                    if ($session->samples()->count() === 0 || $session->panelists()->count() === 0) {
                        Notification::make()->title('Koppel eerst monsters en panelleden aan de sessie.')->warning()->send();

                        return;
                    }

                    GenerateServingScheduleJob::dispatch($session->getKey());

                    Notification::make()->title('Het schema wordt gegenereerd')->body('Ververs de pagina over enkele seconden.')->success()->send();
                }),
            Action::make('start')
                ->label('Sessie starten')
                ->icon(Heroicon::OutlinedPlay)
                ->visible(fn () => $this->canCoordinate() && $this->getRecord()->status === SessionStatus::Planned && $this->getRecord()->schedule_generated_at !== null)
                ->action(function (): void {
                    $this->getRecord()->forceFill(['status' => SessionStatus::Running])->save();
                    Notification::make()->title('Sessie gestart')->success()->send();
                }),
            Action::make('close')
                ->label('Sessie afsluiten')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Na het afsluiten kunnen panelleden geen kaarten meer indienen voor deze sessie.')
                ->visible(fn () => $this->canCoordinate() && $this->getRecord()->status === SessionStatus::Running)
                ->action(function (): void {
                    $this->getRecord()->forceFill(['status' => SessionStatus::Closed])->save();
                    Notification::make()->title('Sessie afgesloten')->success()->send();
                }),
            EditAction::make()->visible(fn () => $this->canCoordinate()),
        ];
    }

    private function canCoordinate(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::Coordinator->value, StaffRole::Admin->value]) ?? false;
    }
}
