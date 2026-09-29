<?php

namespace App\Domain\Commerce\Gateways;

use App\Domain\Commerce\Contracts\AccountingGateway;
use App\Domain\Commerce\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Tot het boekhoudpakket bekend is: alleen loggen.
 */
final class NullAccountingGateway implements AccountingGateway
{
    public function syncInvoice(Invoice $invoice): ?string
    {
        Log::info('Factuur niet gesynchroniseerd (geen boekhoudkoppeling geconfigureerd).', ['invoice' => $invoice->number]);

        return null;
    }
}
