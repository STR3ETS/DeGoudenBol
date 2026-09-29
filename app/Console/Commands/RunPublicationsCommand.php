<?php

namespace App\Console\Commands;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\RevealProvinces;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use Illuminate\Console\Command;
use Throwable;

/**
 * Publiceert goedgekeurde batches waarvan het geplande moment is aangebroken (di/vr 12:00) en
 * onthult op de publicatiedag de bevroren provincies op hun eigen tijd (besluit 20).
 */
class RunPublicationsCommand extends Command
{
    protected $signature = 'publication:run';

    protected $description = 'Publiceert goedgekeurde publicatiebatches en onthult bevroren provincies zodra hun moment verstreken is';

    public function handle(PublishBatch $publish, RevealProvinces $reveal): int
    {
        $due = PublicationBatch::query()
            ->where('status', BatchStatus::Approved)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        foreach ($due as $batch) {
            try {
                $publish($batch);
                $this->info("Gepubliceerd: {$batch->label()}");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Mislukt: {$batch->label()} – {$exception->getMessage()}");
            }
        }

        $this->line($due->isEmpty() ? 'Geen batches klaar voor publicatie.' : "{$due->count()} batch(es) verwerkt.");

        foreach (Edition::query()->where('status', EditionStatus::Frozen)->get() as $edition) {
            foreach ($reveal($edition) as $province) {
                $this->info("Onthuld: {$province} ({$edition->name})");
            }
        }

        return self::SUCCESS;
    }
}
