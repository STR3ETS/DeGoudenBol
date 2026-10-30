<?php

namespace App\Console\Commands;

use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Support\DutchTime;
use App\Support\Mail\SendOnce;
use Illuminate\Console\Command;

/**
 * Herinnering de dag vóór het aanlevermoment (briefing 29 okt §11). Eén keer per inschrijving per
 * aanlevermoment, ook als het commando vaker draait.
 */
class SendDeliveryRemindersCommand extends Command
{
    protected $signature = 'deliveries:remind';

    protected $description = 'Stuurt bakkers die morgen aanleveren een herinnering met hun deelnemercode';

    public function handle(SendOnce $mail): int
    {
        $tomorrow = DutchTime::display(now())->addDay();
        $count = 0;

        Entry::query()
            ->where('status', EntryStatus::Scheduled)
            ->whereHas('deliverySlot', fn ($query) => $query->whereBetween('starts_at', [$tomorrow->startOfDay()->utc(), $tomorrow->endOfDay()->utc()]))
            ->with(['company', 'deliverySlot'])
            ->each(function (Entry $entry) use ($mail, &$count): void {
                foreach ($entry->company->owners() as $owner) {
                    if ($mail->send($owner, new DeliveryScheduledNotification($entry, reminder: true), "entry:{$entry->getKey()}:reminder:{$entry->deliverySlot->starts_at->toDateString()}")) {
                        $count++;
                    }
                }
            });

        $this->components->info("{$count} herinnering(en) verstuurd.");

        return self::SUCCESS;
    }
}
