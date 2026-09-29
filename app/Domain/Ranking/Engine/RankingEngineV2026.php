<?php

namespace App\Domain\Ranking\Engine;

use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Ranking\Data\RankedEntry;
use App\Domain\Ranking\Data\RankedPosition;
use App\Domain\Ranking\Data\RankingSnapshotData;
use App\Domain\Ranking\Enums\PositionLabel;

/**
 * Pure, geversioneerde rankingfunctie (docs/04 §3). Dezelfde invoer geeft altijd dezelfde lijst;
 * niemand kan de volgorde handmatig aanpassen.
 *
 * 1. alleen cijfers >= publicatiedrempel
 * 2. sorteren op cijfer (één decimaal), hoogste eerst
 * 3. gelijk cijfer: beslisregels in tie_break_order (gemiddelde per onderdeel, hoogste eerst)
 * 4. nog gelijk: gedeelde plaats; op plaats 1 of op de grens van de lijst → beslissende beoordeling nodig;
 *    een besliste beslisronde (nieuwe monsters, zelfde deelnemers) legt de onderlinge volgorde vast
 * 5. labels ten opzichte van de vorige snapshot: new / up / down / same
 */
final class RankingEngineV2026
{
    public const string VERSION = '2026.1';

    /**
     * @param  list<RankedEntry>  $results
     * @param  array<int, int>  $previousPositions  entry_id => positie in de vorige snapshot
     * @param  list<list<int>>  $resolvedOrders  Uitkomsten van beslisrondes: entry_ids in besliste volgorde
     * @param  int|null  $listLength  Lengte van de lijst (provinciaal of landelijk); standaard de provinciale
     */
    public function compute(array $results, EditionSettings $settings, array $previousPositions = [], array $resolvedOrders = [], ?int $listLength = null): RankingSnapshotData
    {
        $threshold = $settings->publishThreshold;
        $order = $settings->tieBreakOrder;
        $listLength ??= $settings->provincialListLength;

        $eligible = array_values(array_filter($results, fn (RankedEntry $entry) => $this->normalize($entry->total, $settings->roundingDecimals) >= $threshold));

        usort($eligible, function (RankedEntry $a, RankedEntry $b) use ($order, $settings): int {
            $comparison = $this->compare($a, $b, $order, $settings->roundingDecimals);

            // Volledig gelijk: vaste, inhoudsloze volgorde zodat de uitkomst deterministisch blijft.
            return $comparison !== 0 ? $comparison : $a->entryId <=> $b->entryId;
        });

        $positions = [];
        $count = count($eligible);
        $index = 0;

        while ($index < $count) {
            $groupStart = $index;
            $group = [$eligible[$index]];

            while ($index + 1 < $count && $this->compare($eligible[$index], $eligible[$index + 1], $order, $settings->roundingDecimals) === 0) {
                $index++;
                $group[] = $eligible[$index];
            }

            $position = $groupStart + 1;
            $resolved = count($group) > 1 ? $this->resolvedOrderFor($group, $resolvedOrders) : null;

            if ($resolved !== null) {
                // Beslisronde bepaalt de onderlinge volgorde; iedere deelnemer krijgt een eigen plaats.
                foreach ($resolved as $offset => $entry) {
                    $positions[] = new RankedPosition(
                        entryId: $entry->entryId,
                        position: $position + $offset,
                        total: $this->normalize($entry->total, $settings->roundingDecimals),
                        tieGroup: null,
                        label: $this->label($entry->entryId, $position + $offset, $previousPositions),
                        needsTieBreak: false,
                    );
                }

                $index++;

                continue;
            }

            $size = count($group);
            $isShared = $size > 1;
            $lastOfGroup = $position + $size - 1;
            $needsTieBreak = $isShared && ($position === 1 || ($position <= $listLength && $lastOfGroup > $listLength));

            foreach ($group as $entry) {
                $positions[] = new RankedPosition(
                    entryId: $entry->entryId,
                    position: $position,
                    total: $this->normalize($entry->total, $settings->roundingDecimals),
                    tieGroup: $isShared ? $position : null,
                    label: $this->label($entry->entryId, $position, $previousPositions),
                    needsTieBreak: $needsTieBreak,
                );
            }

            $index++;
        }

        return new RankingSnapshotData(self::VERSION, $this->inputHash($results, $settings, $resolvedOrders, $listLength), $positions);
    }

    /**
     * Onderlinge volgorde van een groep gelijke deelnemers op basis van nieuwe (beslisronde-)resultaten.
     *
     * @param  list<RankedEntry>  $results
     * @return list<int>
     */
    public function order(array $results, EditionSettings $settings): array
    {
        $sorted = $results;

        usort($sorted, function (RankedEntry $a, RankedEntry $b) use ($settings): int {
            $byRaw = round($b->total, 4) <=> round($a->total, 4);

            if ($byRaw !== 0) {
                return $byRaw;
            }

            $comparison = $this->compare($a, $b, $settings->tieBreakOrder, 4);

            return $comparison !== 0 ? $comparison : $a->entryId <=> $b->entryId;
        });

        return array_map(fn (RankedEntry $entry) => $entry->entryId, $sorted);
    }

    /**
     * @param  list<RankedEntry>  $group
     * @param  list<list<int>>  $resolvedOrders
     * @return list<RankedEntry>|null
     */
    private function resolvedOrderFor(array $group, array $resolvedOrders): ?array
    {
        $groupIds = array_map(fn (RankedEntry $entry) => $entry->entryId, $group);
        sort($groupIds);

        foreach ($resolvedOrders as $resolvedOrder) {
            $ids = array_values(array_map('intval', $resolvedOrder));
            $sortedIds = $ids;
            sort($sortedIds);

            if ($sortedIds !== $groupIds) {
                continue;
            }

            $byId = [];

            foreach ($group as $entry) {
                $byId[$entry->entryId] = $entry;
            }

            return array_values(array_map(fn (int $id) => $byId[$id], $ids));
        }

        return null;
    }

    /**
     * @param  list<string>  $order
     */
    private function compare(RankedEntry $a, RankedEntry $b, array $order, int $decimals): int
    {
        $byTotal = $this->normalize($b->total, $decimals) <=> $this->normalize($a->total, $decimals);

        if ($byTotal !== 0) {
            return $byTotal;
        }

        foreach ($order as $code) {
            $byCriterion = round((float) ($b->criterionAverages[$code] ?? 0), 4) <=> round((float) ($a->criterionAverages[$code] ?? 0), 4);

            if ($byCriterion !== 0) {
                return $byCriterion;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, int>  $previous
     */
    private function label(int $entryId, int $position, array $previous): PositionLabel
    {
        if (! array_key_exists($entryId, $previous)) {
            return PositionLabel::New;
        }

        return match (true) {
            $position < $previous[$entryId] => PositionLabel::Up,
            $position > $previous[$entryId] => PositionLabel::Down,
            default => PositionLabel::Same,
        };
    }

    private function normalize(float $total, int $decimals): float
    {
        return round($total, $decimals);
    }

    /**
     * @param  list<RankedEntry>  $results
     * @param  list<list<int>>  $resolvedOrders
     */
    private function inputHash(array $results, EditionSettings $settings, array $resolvedOrders, int $listLength): string
    {
        $rows = array_map(function (RankedEntry $entry): array {
            $averages = array_map(fn ($value) => round((float) $value, 4), $entry->criterionAverages);
            ksort($averages);

            return ['e' => $entry->entryId, 't' => round($entry->total, 4), 'a' => $averages];
        }, $results);

        usort($rows, fn (array $a, array $b) => $a['e'] <=> $b['e']);

        $orders = array_map(fn (array $order) => array_values(array_map('intval', $order)), $resolvedOrders);
        sort($orders);

        $canonical = json_encode([
            'engine' => self::VERSION,
            'threshold' => $settings->publishThreshold,
            'tie_break_order' => $settings->tieBreakOrder,
            'list_length' => $listLength,
            'resolved' => $orders,
            'rows' => $rows,
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical);
    }
}
