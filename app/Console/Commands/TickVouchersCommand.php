<?php

namespace App\Console\Commands;

use App\Domain\Vouchers\Actions\AnonymizeWinners;
use App\Domain\Vouchers\Actions\TickCampaigns;
use Illuminate\Console\Command;

/**
 * Tijdpad van de cadeaubonnen: openen, sluiten met tekort, laten vervallen en (na 31 maart) anonimiseren.
 */
class TickVouchersCommand extends Command
{
    protected $signature = 'vouchers:tick';

    protected $description = 'Opent en sluit cadeaubonnenacties, laat bonnen vervallen en anonimiseert winnaars na de bewaartermijn';

    public function handle(TickCampaigns $tick, AnonymizeWinners $anonymize): int
    {
        $result = $tick();
        $anonymized = $anonymize();

        $this->line("Geopend: {$result['opened']} · gesloten: {$result['closed']} · vervallen bonnen: {$result['expired_vouchers']} · afgelopen acties: {$result['expired_campaigns']} · geanonimiseerd: {$anonymized}");

        return self::SUCCESS;
    }
}
