<?php

namespace App\Domain\Intake\Actions;

use App\Domain\Intake\Data\IntakeData;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stap 3 uit het kernproces: monster ontvangen. Maakt het monster in de testketen, start de
 * versheidsklok en legt de koppeling met de inschrijving uitsluitend in de kluis vast.
 */
final class ReceiveSample
{
    public function __construct(
        private readonly VaultService $vault,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Entry $entry, IntakeData $data, ?User $receiver = null, SampleRound $round = SampleRound::Provincial): Sample
    {
        if (! in_array($entry->status, self::receivableStatuses($round), true)) {
            throw new InvalidArgumentException('Voor deze inschrijving kan nu geen monster worden ontvangen (status: '.$entry->status->getLabel().', ronde: '.$round->getLabel().').');
        }

        $entry->loadMissing(['edition', 'deliverySlot']);
        $receivedAt = $data->receivedAt ?? now();
        $windowMinutes = $entry->edition->settings->freshnessWindowMinutes;

        return DB::connection('testing')->transaction(function () use ($entry, $data, $receiver, $round, $receivedAt, $windowMinutes): Sample {
            $sample = Sample::query()->create([
                'edition_id' => $entry->edition_id,
                'round' => $round,
                'status' => SampleStatus::Received,
                'test_location_id' => $data->testLocationId ?? $entry->deliverySlot?->test_location_id,
                'notes' => $data->notes,
            ]);

            $sample->intake()->create([
                'received_at' => $receivedAt,
                'temperature_c' => $data->temperatureC,
                'piece_count' => $data->pieceCount,
                'photo_path' => $data->photoPath,
                'received_by' => $receiver?->getKey(),
                'freshness_expires_at' => $receivedAt->addMinutes($windowMinutes),
            ]);

            $this->vault->link($sample->getKey(), $entry->edition_id, $entry->ulid, 'ontvangst monster '.$round->value);

            // Beslisronde en finale veranderen de status van de inschrijving niet: die is al gepubliceerd.
            if ($round === SampleRound::Provincial) {
                $entry->forceFill(['status' => EntryStatus::Received])->save();
            }

            // Bewust twee losse regels: de auditlog mag de koppeling niet prijsgeven.
            $this->audit->record('entry.received', $entry, ['received_at' => $receivedAt->toIso8601String()], $receiver);
            $this->audit->record('sample.received', null, ['sample' => $sample->ulid, 'pieces' => $data->pieceCount], $receiver);

            return $sample->load('intake');
        });
    }

    /**
     * Provinciale ronde: nog niet ontvangen inschrijvingen. Beslisronde en finale: al gepubliceerde deelnemers.
     *
     * @return list<EntryStatus>
     */
    public static function receivableStatuses(SampleRound $round): array
    {
        return $round === SampleRound::Provincial
            ? [EntryStatus::Registered, EntryStatus::Scheduled, EntryStatus::FreshnessExpired]
            : [EntryStatus::Published, EntryStatus::Confidential, EntryStatus::Linked];
    }
}
