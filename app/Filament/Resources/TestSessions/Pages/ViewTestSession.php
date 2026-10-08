<?php

namespace App\Filament\Resources\TestSessions\Pages;

use App\Filament\Concerns\CoordinatesTestSessions;
use App\Filament\Resources\TestSessions\TestSessionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewTestSession extends ViewRecord
{
    use CoordinatesTestSessions;

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
                ->visible(fn () => $this->canGenerateScheduleFor($this->getRecord()))
                ->action(fn () => $this->generateScheduleFor($this->getRecord())),
            Action::make('start')
                ->label('Sessie starten')
                ->icon(Heroicon::OutlinedPlay)
                ->visible(fn () => $this->canStart($this->getRecord()))
                ->action(fn () => $this->startSession($this->getRecord())),
            Action::make('close')
                ->label('Sessie afsluiten')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Na het afsluiten kunnen panelleden geen kaarten meer indienen voor deze sessie.')
                ->visible(fn () => $this->canClose($this->getRecord()))
                ->action(fn () => $this->closeSession($this->getRecord())),
            EditAction::make()->visible(fn () => $this->canCoordinate()),
        ];
    }
}
