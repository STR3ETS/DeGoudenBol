<?php

namespace Tests\Unit;

use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Ranking\Data\RankedEntry;
use App\Domain\Ranking\Engine\RankingEngineV2026;
use App\Domain\Ranking\Enums\PositionLabel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * De rankingengine uit docs/04 §3 als pure functie.
 */
class RankingEngineTest extends TestCase
{
    private RankingEngineV2026 $engine;

    private EditionSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new RankingEngineV2026;
        $this->settings = new EditionSettings;
    }

    #[Test]
    public function it_sorts_by_total_and_filters_below_the_threshold(): void
    {
        $snapshot = $this->engine->compute([
            $this->entry(1, 7.4),
            $this->entry(2, 8.9),
            $this->entry(3, 4.9),
            $this->entry(4, 8.1),
        ], $this->settings);

        $this->assertSame([2, 4, 1], array_map(fn ($p) => $p->entryId, $snapshot->positions));
        $this->assertSame([1, 2, 3], array_map(fn ($p) => $p->position, $snapshot->positions));
        $this->assertSame(RankingEngineV2026::VERSION, $snapshot->engineVersion);
        $this->assertSame(64, strlen($snapshot->inputHash));
    }

    #[Test]
    public function ties_are_broken_by_the_criteria_in_the_configured_order(): void
    {
        $snapshot = $this->engine->compute([
            $this->entry(1, 8.0, ['smaak' => 20.0, 'structuur_luchtigheid' => 16.0, 'versheid' => 8.0, 'vulling_verhouding' => 12.0]),
            $this->entry(2, 8.0, ['smaak' => 20.0, 'structuur_luchtigheid' => 17.0, 'versheid' => 7.0, 'vulling_verhouding' => 12.0]),
            $this->entry(3, 8.0, ['smaak' => 21.0, 'structuur_luchtigheid' => 10.0, 'versheid' => 5.0, 'vulling_verhouding' => 12.0]),
            $this->entry(4, 8.0, ['smaak' => 20.0, 'structuur_luchtigheid' => 16.0, 'versheid' => 9.0, 'vulling_verhouding' => 11.0]),
        ], $this->settings);

        // smaak beslist eerst (3), dan structuur (2), dan versheid (4 boven 1).
        $this->assertSame([3, 2, 4, 1], array_map(fn ($p) => $p->entryId, $snapshot->positions));
        $this->assertSame([1, 2, 3, 4], array_map(fn ($p) => $p->position, $snapshot->positions));
        $this->assertSame([null, null, null, null], array_map(fn ($p) => $p->tieGroup, $snapshot->positions));
    }

    #[Test]
    public function fully_equal_entries_share_a_place_and_the_next_place_is_skipped(): void
    {
        $snapshot = $this->engine->compute([
            $this->entry(1, 9.0),
            $this->entry(2, 8.0, ['smaak' => 20.0]),
            $this->entry(3, 8.0, ['smaak' => 20.0]),
            $this->entry(4, 7.0),
        ], $this->settings);

        $this->assertSame([1, 2, 2, 4], array_map(fn ($p) => $p->position, $snapshot->positions));
        $this->assertSame([null, 2, 2, null], array_map(fn ($p) => $p->tieGroup, $snapshot->positions));
        $this->assertFalse($snapshot->needsTieBreak(), 'Een gedeelde tweede plaats vraagt geen beslisronde.');
    }

    #[Test]
    public function a_shared_first_place_or_a_tie_on_the_top_10_boundary_needs_a_tie_break(): void
    {
        $shared = $this->engine->compute([$this->entry(1, 9.0), $this->entry(2, 9.0)], $this->settings);
        $this->assertTrue($shared->needsTieBreak());
        $this->assertTrue($shared->positions[0]->needsTieBreak && $shared->positions[1]->needsTieBreak);

        $entries = [];
        for ($i = 1; $i <= 9; $i++) {
            $entries[] = $this->entry($i, 9.0 - $i * 0.1);
        }
        $entries[] = $this->entry(10, 7.0);
        $entries[] = $this->entry(11, 7.0);
        $entries[] = $this->entry(12, 6.0);

        $boundary = $this->engine->compute($entries, $this->settings);
        $tenth = array_values(array_filter($boundary->positions, fn ($p) => $p->position === 10));

        $this->assertCount(2, $tenth);
        $this->assertTrue($tenth[0]->needsTieBreak);
        $this->assertSame(12, $boundary->positions[11]->position);
        $this->assertFalse($boundary->positions[11]->needsTieBreak);

        $inside = $this->engine->compute([
            ...array_slice($entries, 0, 8),
            $this->entry(10, 7.0),
            $this->entry(11, 7.0),
        ], $this->settings);
        $this->assertFalse($inside->needsTieBreak(), 'Gedeelde plaats 9 valt volledig binnen de Top 10.');
    }

    #[Test]
    public function labels_compare_with_the_previous_snapshot(): void
    {
        $snapshot = $this->engine->compute([
            $this->entry(1, 8.0),
            $this->entry(2, 9.0),
            $this->entry(3, 7.5),
            $this->entry(4, 7.0),
        ], $this->settings, previousPositions: [1 => 1, 2 => 2, 4 => 3]);

        $labels = [];
        foreach ($snapshot->positions as $position) {
            $labels[$position->entryId] = $position->label;
        }

        $this->assertSame(PositionLabel::Up, $labels[2]);
        $this->assertSame(PositionLabel::Down, $labels[1]);
        $this->assertSame(PositionLabel::New, $labels[3]);
        $this->assertSame(PositionLabel::Down, $labels[4]);
    }

    #[Test]
    public function a_decided_tie_break_fixes_the_order_within_the_shared_place(): void
    {
        $results = [$this->entry(1, 9.0), $this->entry(2, 9.0), $this->entry(3, 8.0)];

        $undecided = $this->engine->compute($results, $this->settings);
        $this->assertTrue($undecided->needsTieBreak());

        $decided = $this->engine->compute($results, $this->settings, resolvedOrders: [[2, 1]]);

        $this->assertFalse($decided->needsTieBreak());
        $this->assertSame([2, 1, 3], array_map(fn ($p) => $p->entryId, $decided->positions));
        $this->assertSame([1, 2, 3], array_map(fn ($p) => $p->position, $decided->positions));
        $this->assertSame([null, null, null], array_map(fn ($p) => $p->tieGroup, $decided->positions));
        $this->assertNotSame($undecided->inputHash, $decided->inputHash, 'De beslisronde maakt deel uit van de invoer.');

        // Een beslisronde voor een andere groep (of onvolledig) verandert niets.
        $other = $this->engine->compute($results, $this->settings, resolvedOrders: [[1, 3]]);
        $this->assertTrue($other->needsTieBreak());
    }

    #[Test]
    public function the_national_list_uses_its_own_length_for_the_boundary_flag(): void
    {
        $results = [];
        for ($i = 1; $i <= 5; $i++) {
            $results[] = $this->entry($i, 9.0 - $i * 0.1);
        }
        $results[] = $this->entry(6, 8.0);
        $results[] = $this->entry(7, 8.0);

        $provincial = $this->engine->compute($results, $this->settings);
        $this->assertFalse($provincial->needsTieBreak(), 'Gedeelde plaats 6 valt binnen een Top 10.');

        $national = $this->engine->compute($results, $this->settings, listLength: 5);
        $this->assertFalse($national->needsTieBreak(), 'Gedeelde plaats 6 ligt volledig buiten een Top 5.');

        $boundary = $this->engine->compute([...array_slice($results, 0, 4), $this->entry(6, 8.0), $this->entry(7, 8.0)], $this->settings, listLength: 5);
        $this->assertTrue($boundary->needsTieBreak(), 'Gedeelde plaats 5 ligt op de grens van een Top 5.');
    }

    #[Test]
    public function order_ranks_tie_break_results_on_raw_total_then_criteria(): void
    {
        $order = $this->engine->order([
            $this->entry(1, 81.25, ['smaak' => 20.0]),
            $this->entry(2, 81.25, ['smaak' => 21.0]),
            $this->entry(3, 84.0),
        ], $this->settings);

        $this->assertSame([3, 2, 1], $order);
    }

    #[Test]
    public function fewer_than_ten_results_or_none_at_all_are_handled_without_padding(): void
    {
        $few = $this->engine->compute([$this->entry(1, 6.0), $this->entry(2, 5.0)], $this->settings);
        $this->assertCount(2, $few->topList(10));

        $none = $this->engine->compute([$this->entry(1, 4.0)], $this->settings);
        $this->assertSame([], $none->positions);
    }

    #[Test]
    public function the_same_input_always_gives_the_same_hash_and_order_regardless_of_input_order(): void
    {
        $a = $this->engine->compute([$this->entry(1, 8.0), $this->entry(2, 8.0), $this->entry(3, 7.0)], $this->settings);
        $b = $this->engine->compute([$this->entry(3, 7.0), $this->entry(2, 8.0), $this->entry(1, 8.0)], $this->settings);

        $this->assertSame($a->inputHash, $b->inputHash);
        $this->assertSame(array_map(fn ($p) => [$p->entryId, $p->position], $a->positions), array_map(fn ($p) => [$p->entryId, $p->position], $b->positions));
    }

    /**
     * @param  array<string, float>  $averages
     */
    private function entry(int $id, float $total, array $averages = []): RankedEntry
    {
        return new RankedEntry($id, $total, $averages + ['smaak' => 0.0, 'structuur_luchtigheid' => 0.0, 'versheid' => 0.0, 'vulling_verhouding' => 0.0]);
    }
}
