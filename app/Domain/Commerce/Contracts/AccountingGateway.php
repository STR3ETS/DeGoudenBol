<?php

namespace App\Domain\Commerce\Contracts;

use App\Domain\Commerce\Models\Invoice;

/**
 * Koppeling met het boekhoudpakket van Bennie (Moneybird of Exact Online, nog te horen).
 */
interface AccountingGateway
{
    /**
     * Maakt of werkt de factuur bij in het boekhoudpakket en geeft het externe id terug.
     */
    public function syncInvoice(Invoice $invoice): ?string;
}
