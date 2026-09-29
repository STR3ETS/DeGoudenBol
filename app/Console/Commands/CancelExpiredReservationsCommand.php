<?php

namespace App\Console\Commands;

use App\Domain\Participants\Actions\CancelExpiredReservations;
use Illuminate\Console\Command;

class CancelExpiredReservationsCommand extends Command
{
    protected $signature = 'entries:cancel-expired';

    protected $description = 'Annuleert onbetaalde inschrijvingen waarvan de reservering is verlopen';

    public function handle(CancelExpiredReservations $action): int
    {
        $count = $action();

        $this->components->info("{$count} verlopen reservering(en) geannuleerd.");

        return self::SUCCESS;
    }
}
