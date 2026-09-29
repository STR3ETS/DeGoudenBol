<?php

namespace App\Domain\Testing\Jobs;

use App\Domain\Testing\Actions\GenerateServingSchedule;
use App\Domain\Testing\Models\TestSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Systeemproces met kluistoegang: vertaalt conflicten en allergenen naar uitsluitingen
 * zonder dat de testcoördinatie zelf in de kluis hoeft.
 */
class GenerateServingScheduleJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $sessionId) {}

    public function handle(GenerateServingSchedule $generate): void
    {
        $session = TestSession::query()->findOrFail($this->sessionId);

        $generate($session);
    }
}
