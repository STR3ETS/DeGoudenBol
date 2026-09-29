<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Data\RankedPosition;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Enums\SnapshotStatus;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Models\Finalist;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Bevriezing (docs/04 §3): in één transactie de definitieve snapshot per provincie, de
 * provinciewinnaars en de finale-inschrijvingen. De lijsten blijven onder embargo tot de reveal
 * op de publicatiedag; erkenningen en assets volgen via EditionFrozen.
 */
final class FreezeEdition
{
    public function __construct(
        private readonly RecomputeRanking $recompute,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Edition $edition, ?User $by = null): Edition
    {
        if (! in_array($edition->status, [EditionStatus::Testing, EditionStatus::Closed], true)) {
            throw new LogicException('Alleen een editie in de testfase of gesloten fase kan worden bevroren (status: '.$edition->status->getLabel().').');
        }

        $edition->loadMissing('provinces');
        $pending = [];
        $computed = [];

        foreach ($edition->provinces as $province) {
            $data = $this->recompute->provincial($edition, $province->getKey());
            $computed[$province->getKey()] = $data;

            if ($data->needsTieBreak()) {
                $pending[] = $province->name;
            }
        }

        if ($pending !== []) {
            throw new LogicException('Beslissende beoordeling nog niet afgerond in: '.implode(', ', $pending).'. De bevriezing wacht daarop.');
        }

        $frozen = DB::transaction(function () use ($edition, $by): Edition {
            $listLength = $edition->settings->provincialListLength;
            $finalists = 0;

            foreach ($edition->provinces as $province) {
                $snapshot = ($this->recompute)($edition, $province->getKey(), null, SnapshotStatus::Frozen, publish: false);
                $winner = $snapshot->positions()->where('position', 1)->first();

                if ($winner === null || Finalist::query()->where('edition_id', $edition->getKey())->where('entry_id', $winner->entry_id)->exists()) {
                    continue;
                }

                if ($finalists >= $edition->settings->maxFinalists) {
                    break;
                }

                Finalist::query()->create([
                    'edition_id' => $edition->getKey(),
                    'province_id' => $province->getKey(),
                    'entry_id' => $winner->entry_id,
                    'origin' => 'province_winner',
                    'province_position' => 1,
                    'status' => FinalistStatus::Invited,
                    'invited_at' => now(),
                ]);

                $finalists++;
            }

            $edition->forceFill(['status' => EditionStatus::Frozen])->save();

            $this->audit->record('edition.frozen', $edition, ['finalists' => $finalists, 'list_length' => $listLength], $by);

            return $edition;
        });

        EditionFrozen::dispatch($frozen);

        return $frozen;
    }

    /**
     * @return list<RankedPosition>
     */
    public function preview(Edition $edition, int $provinceId): array
    {
        return $this->recompute->provincial($edition, $provinceId)->positions;
    }
}
