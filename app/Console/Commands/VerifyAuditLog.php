<?php

namespace App\Console\Commands;

use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Console\Command;

class VerifyAuditLog extends Command
{
    protected $signature = 'audit:verify
        {--hash : Toon alleen de laatste hash (voor externe borging, besluit 22)}';

    protected $description = 'Controleert de hashketen van de auditlog';

    public function handle(AuditLogger $logger): int
    {
        if ($this->option('hash')) {
            $this->line($logger->latestHash() ?? '');

            return self::SUCCESS;
        }

        $result = $logger->verify();

        if ($result['ok']) {
            $this->components->info("Auditlog intact: {$result['checked']} regels gecontroleerd.");

            return self::SUCCESS;
        }

        $this->components->error("Auditlog gebroken bij regel #{$result['broken_id']}: {$result['reason']} ({$result['checked']} regels gecontroleerd).");

        return self::FAILURE;
    }
}
