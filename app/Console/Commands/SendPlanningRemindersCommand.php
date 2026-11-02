<?php

namespace App\Console\Commands;

use App\Domain\Intake\Notifications\PlanDeliveryReminderNotification;
use App\Domain\Intake\Services\PlanningOptions;
use App\Domain\Participants\Models\Entry;
use App\Support\DutchTime;
use App\Support\Mail\SendOnce;
use Illuminate\Console\Command;

/**
 * Dagelijkse herinnering voor betaalde inschrijvingen zonder aanlevermoment of ophaalvrijdag (besluit Raphael
 * 2 nov 2026). Eén per dag, hoogstens drie, en alleen als er nog een vrije plek te kiezen is. Na twee
 * herinneringen toont het dashboard een taak om de bakker te bellen (WorkQueue).
 */
class SendPlanningRemindersCommand extends Command
{
    public const int MAX_REMINDERS = 3;

    public const int DASHBOARD_AFTER = 2;

    protected $signature = 'deliveries:plan-reminder';

    protected $description = 'Herinnert betaalde bakkers die nog geen aanlevermoment of ophaalvrijdag hebben gekozen';

    public function handle(SendOnce $mail, PlanningOptions $options): int
    {
        $startOfToday = DutchTime::display(now())->startOfDay()->utc();
        $hasRoom = [];
        $count = 0;

        Entry::query()
            ->awaitingPlanning()
            ->where('planning_reminders', '<', self::MAX_REMINDERS)
            ->where(fn ($query) => $query->whereNull('planning_reminded_at')->orWhere('planning_reminded_at', '<', $startOfToday))
            // Wie net heeft betaald, krijgt eerst een dag de tijd (de bedankpagina vraagt het al).
            ->where('confirmed_at', '<=', now()->subHours(20))
            ->with(['company', 'edition', 'order'])
            ->each(function (Entry $entry) use ($mail, $options, &$hasRoom, &$count): void {
                $key = $entry->edition_id.':'.($entry->pickup_prepaid ? 'pickup' : 'slot');
                $hasRoom[$key] ??= $options->hasRoom($entry->edition, $entry->pickup_prepaid);

                if (! $hasRoom[$key]) {
                    return;
                }

                $number = $entry->planning_reminders + 1;

                foreach ($entry->company->owners() as $owner) {
                    if ($mail->send($owner, new PlanDeliveryReminderNotification($entry), "entry:{$entry->getKey()}:plan-reminder:{$number}")) {
                        $count++;
                    }
                }

                $entry->forceFill(['planning_reminders' => $number, 'planning_reminded_at' => now()])->save();
            });

        $this->components->info("{$count} herinnering(en) verstuurd.");

        return self::SUCCESS;
    }
}
