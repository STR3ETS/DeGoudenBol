<?php

namespace App\Console\Commands;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Actions\RecomputeRanking;
use App\Domain\Ranking\Engine\RankingEngineV2026;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Services\RankingInputs;
use Illuminate\Console\Command;

/**
 * Dagelijkse integriteitsbewaking: rekent iedere gepubliceerde lijst opnieuw uit de brondata en
 * meldt afwijkingen. Met --recompute wordt een afwijkende lijst als nieuwe snapshot vastgelegd
 * (bijvoorbeeld na een terugtrekking).
 */
class VerifyRankingsCommand extends Command
{
    protected $signature = 'ranking:verify {--recompute : Leg een nieuwe snapshot vast als de invoer veranderd is}';

    protected $description = 'Controleert of de gepubliceerde ranglijsten nog uit de brondata volgen';

    public function handle(RankingInputs $inputs, RankingEngineV2026 $engine, RecomputeRanking $recompute, AuditLogger $audit): int
    {
        $edition = Edition::current();

        if ($edition === null) {
            $this->line('Geen actieve editie.');

            return self::SUCCESS;
        }

        $mismatches = 0;

        foreach ($edition->provinces as $province) {
            $latest = RankingSnapshot::latestForProvince($edition->getKey(), $province->getKey());

            if ($latest === null) {
                continue;
            }

            $expected = $engine->compute($inputs->forProvince($edition, $province->getKey()), $edition->settings);

            if (hash_equals($latest->input_hash, $expected->inputHash)) {
                $this->line("{$province->name}: klopt ({$latest->positions()->count()} posities)");

                continue;
            }

            $mismatches++;
            $this->warn("{$province->name}: invoer wijkt af van snapshot #{$latest->getKey()}");
            $audit->record('ranking.mismatch', $latest, ['province' => $province->slug, 'expected_hash' => $expected->inputHash], null);

            if ($this->option('recompute')) {
                $snapshot = $recompute($edition, $province->getKey());
                $this->info("{$province->name}: nieuwe snapshot #{$snapshot->getKey()} vastgelegd");
            }
        }

        $this->line($mismatches === 0 ? 'Alle lijsten volgen uit de brondata.' : "{$mismatches} afwijking(en) gevonden.");

        return $mismatches === 0 || $this->option('recompute') ? self::SUCCESS : self::FAILURE;
    }
}
