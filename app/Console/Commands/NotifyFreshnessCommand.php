<?php

namespace App\Console\Commands;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\StaffNotifier;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Filament\Resources\Samples\SampleResource;
use App\Support\DutchTime;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Waarschuwt in de bel voor monsters die bijna niet meer vers zijn en nog niet zijn beoordeeld.
 * Draait iedere tien minuten; het venster van tien minuten zorgt dat ieder monster één keer meldt.
 */
class NotifyFreshnessCommand extends Command
{
    protected $signature = 'freshness:notify {--minutes=30 : Waarschuw zoveel minuten voordat de versheid verloopt}';

    protected $description = 'Waarschuwt testcoördinatie en scorecontrole voor monsters die bijna niet meer vers zijn';

    public function handle(StaffNotifier $notifier): int
    {
        $minutes = max(10, (int) $this->option('minutes'));
        $now = now();

        $samples = Sample::query()
            ->whereIn('status', [SampleStatus::Received, SampleStatus::Numbered, SampleStatus::Scheduled])
            ->whereHas('intake', fn (Builder $query) => $query->whereBetween('freshness_expires_at', [
                $now->addMinutes($minutes - 10),
                $now->addMinutes($minutes),
            ]))
            ->with('intake')
            ->get();

        foreach ($samples as $sample) {
            $notifier->notify(
                [StaffRole::Coordinator, StaffRole::Reviewer],
                "Monster {$sample->label()} verloopt om ".DutchTime::format($sample->intake->freshness_expires_at, 'HH:mm'),
                'Nog niet beoordeeld. Serveer het nu uit of laat het opnieuw aanleveren.',
                SampleResource::getUrl('view', ['record' => $sample]),
                null,
                'heroicon-o-fire',
                'danger',
            );
        }

        $this->components->info("{$samples->count()} versheidswaarschuwing(en) verstuurd.");

        return self::SUCCESS;
    }
}
