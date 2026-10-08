<?php

namespace App\Filament\Concerns;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Jobs\GenerateServingScheduleJob;
use App\Domain\Testing\Models\TestSession;
use Filament\Notifications\Notification;

/**
 * De drie handelingen van testcoördinatie op een sessie, gedeeld door de sessiepagina en de
 * testdag-cockpit: schema genereren, starten, afsluiten. De regels staan op één plek.
 */
trait CoordinatesTestSessions
{
    protected function canCoordinate(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::Coordinator->value, StaffRole::Admin->value]) ?? false;
    }

    protected function canGenerateScheduleFor(TestSession $session): bool
    {
        return $this->canCoordinate()
            && $session->status->acceptsScorecards()
            && ! $session->scorecards()->exists();
    }

    protected function canStart(TestSession $session): bool
    {
        return $this->canCoordinate()
            && $session->status === SessionStatus::Planned
            && $session->schedule_generated_at !== null;
    }

    protected function canClose(TestSession $session): bool
    {
        return $this->canCoordinate() && $session->status === SessionStatus::Running;
    }

    protected function generateScheduleFor(TestSession $session): void
    {
        if ($session->samples()->count() === 0 || $session->panelists()->count() === 0) {
            Notification::make()->title('Koppel eerst monsters en panelleden aan de sessie.')->warning()->send();

            return;
        }

        GenerateServingScheduleJob::dispatch($session->getKey());

        Notification::make()->title('Het schema wordt gegenereerd')->body('Ververs de pagina over enkele seconden.')->success()->send();
    }

    protected function startSession(TestSession $session): void
    {
        $session->forceFill(['status' => SessionStatus::Running])->save();

        Notification::make()->title('Sessie gestart')->success()->send();
    }

    protected function closeSession(TestSession $session): void
    {
        $session->forceFill(['status' => SessionStatus::Closed])->save();

        Notification::make()->title('Sessie afgesloten')->success()->send();
    }
}
