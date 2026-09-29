<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Testing\Contracts\ExclusionSource;
use App\Domain\Testing\Data\ScheduleSummary;
use App\Domain\Testing\Enums\AssignmentStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Stap 5 uit het kernproces: uitserveerschema per panellid, zonder conflicten en allergenen.
 * Ieder panellid begint op een ander monster (rotatie) zodat de volgorde-effecten spreiden.
 */
final class GenerateServingSchedule
{
    public function __construct(private readonly ExclusionSource $exclusions) {}

    public function __invoke(TestSession $session): ScheduleSummary
    {
        $session->loadMissing(['samples.intake', 'panelists']);

        if ($session->scorecards()->exists()) {
            throw new LogicException('Er zijn al scorekaarten ingediend; het schema kan niet opnieuw worden gegenereerd.');
        }

        $samples = $session->samples->filter(fn (Sample $sample) => $sample->status->canBeServed())->values();
        $panelists = $session->panelists->values();

        if ($samples->isEmpty() || $panelists->isEmpty()) {
            throw new LogicException('Koppel eerst monsters en panelleden aan de sessie.');
        }

        $excluded = [];

        foreach ($this->exclusions->exclusionsFor($session) as $exclusion) {
            $excluded[$exclusion->panelistId][$exclusion->sampleId] = $exclusion->reason;
        }

        $warnings = [];

        foreach ($samples as $sample) {
            $expiresAt = $sample->intake?->freshness_expires_at;

            if ($expiresAt === null) {
                $warnings[] = "Monster {$sample->label()} heeft geen ontvangstregistratie.";
            } elseif ($expiresAt->lessThan($session->starts_at)) {
                $warnings[] = "Monster {$sample->label()} is bij aanvang van de sessie niet meer vers.";
            }
        }

        return DB::connection('testing')->transaction(function () use ($session, $samples, $panelists, $excluded, $warnings): ScheduleSummary {
            $session->assignments()->delete();
            $session->exclusions()->delete();

            $assignmentCount = 0;
            $exclusionCount = 0;
            $sampleCount = $samples->count();

            foreach ($panelists as $index => $panelist) {
                $order = 1;

                for ($offset = 0; $offset < $sampleCount; $offset++) {
                    $sample = $samples[($index + $offset) % $sampleCount];
                    $reason = $excluded[$panelist->getKey()][$sample->getKey()] ?? null;

                    if ($reason !== null) {
                        $session->exclusions()->create([
                            'panelist_id' => $panelist->getKey(),
                            'sample_id' => $sample->getKey(),
                            'reason_code' => $reason,
                        ]);
                        $exclusionCount++;

                        continue;
                    }

                    $session->assignments()->create([
                        'panelist_id' => $panelist->getKey(),
                        'sample_id' => $sample->getKey(),
                        'serving_order' => $order++,
                        'status' => AssignmentStatus::Pending,
                    ]);
                    $assignmentCount++;
                }
            }

            Sample::query()->whereKey($samples->modelKeys())
                ->where('status', SampleStatus::Numbered)
                ->update(['status' => SampleStatus::Scheduled]);

            $session->forceFill(['schedule_generated_at' => now()])->save();

            return new ScheduleSummary($assignmentCount, $exclusionCount, $panelists->count(), $sampleCount, $warnings);
        });
    }
}
