<?php

namespace App\Domain\Intake\Actions;

use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stap 4 uit het kernproces: oplopend testnummer toekennen (0001, 0002, …; F01 in de finale).
 * Het etiket toont alleen dit nummer.
 */
final class AssignSampleNumber
{
    public function __construct(
        private readonly VaultService $vault,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Sample $sample, ?User $by = null): Sample
    {
        if ($sample->status !== SampleStatus::Received) {
            throw new InvalidArgumentException('Alleen een ontvangen monster zonder nummer kan een testnummer krijgen.');
        }

        $numbered = DB::connection('testing')->transaction(function () use ($sample): Sample {
            $locked = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());

            $highest = Sample::query()
                ->where('edition_id', $locked->edition_id)
                ->where('round', $locked->round)
                ->whereNotNull('sample_number')
                ->pluck('sample_number')
                ->map(fn (string $number) => (int) preg_replace('/\D/', '', $number))
                ->max() ?? 0;

            $locked->forceFill([
                'sample_number' => $locked->round->formatNumber($highest + 1),
                'status' => SampleStatus::Numbered,
            ])->save();

            return $locked;
        });

        $entryUlid = $this->vault->entryUlidForSample($numbered->getKey(), 'registratie testnummer');

        if ($entryUlid !== null) {
            Entry::query()->where('ulid', $entryUlid)->where('status', EntryStatus::Received)
                ->update(['status' => EntryStatus::Numbered]);
        }

        $this->audit->record('sample.numbered', null, ['sample' => $numbered->ulid, 'number' => $numbered->sample_number], $by);

        return $numbered;
    }
}
