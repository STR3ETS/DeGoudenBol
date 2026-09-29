<?php

namespace Tests\Unit;

use App\Domain\Edition\Models\ScoringCriterion;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Testing\Enums\CorrectionStatus;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Domain\Testing\Services\ScoreCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * De scoreberekening uit docs/04 §2 als pure functie: geen database nodig.
 */
class ScoreCalculatorTest extends TestCase
{
    private ScoringModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new ScoringModel(['version' => 1]);
        $this->model->setRelation('criteria', collect([
            new ScoringCriterion(['code' => 'smaak', 'max_points' => 25]),
            new ScoringCriterion(['code' => 'structuur_luchtigheid', 'max_points' => 20]),
            new ScoringCriterion(['code' => 'vulling_verhouding', 'max_points' => 15]),
            new ScoringCriterion(['code' => 'versheid', 'max_points' => 10]),
            new ScoringCriterion(['code' => 'korst_kleur', 'max_points' => 10]),
            new ScoringCriterion(['code' => 'bakgraad_vetopname', 'max_points' => 10]),
            new ScoringCriterion(['code' => 'geur', 'max_points' => 5]),
            new ScoringCriterion(['code' => 'uiterlijk_presentatie', 'max_points' => 5]),
        ]));
    }

    #[Test]
    public function it_averages_per_criterion_sums_and_rounds_half_up_to_one_decimal(): void
    {
        $cards = $this->cards([
            [20, 16, 12, 8, 8, 8, 4, 4], // 80
            [22, 15, 11, 7, 7, 8, 4, 4], // 78
            [18, 14, 10, 8, 7, 7, 3, 3], // 70
            [21, 17, 12, 9, 8, 8, 4, 4], // 83
            [19, 15, 11, 8, 8, 7, 4, 4], // 76
            [20, 16, 12, 8, 8, 8, 4, 4], // 80
        ]);

        $result = (new ScoreCalculator)->calculate($cards, $this->model, new EditionSettings);

        $this->assertSame(6, $result->cardCount);
        $this->assertSame(20.0, $result->criterionAverages['smaak']);
        $this->assertSame(15.5, $result->criterionAverages['structuur_luchtigheid']);
        $this->assertSame(77.8333, $result->totalRaw);
        $this->assertSame(7.8, $result->total);
        $this->assertTrue($result->meetsMinimum());
        $this->assertSame([], $result->outlierCardUuids);
    }

    #[Test]
    public function exact_halves_round_up(): void
    {
        // Gemiddeld 75,5 punten → 7,55 → 7,6 (half-up), niet 7,5.
        $cards = $this->cards([
            [20, 16, 12, 8, 8, 8, 4, 4], // 80
            [15, 15, 11, 7, 7, 8, 4, 4], // 71
        ]);

        $result = (new ScoreCalculator)->calculate($cards, $this->model, new EditionSettings(minValidCards: 2));

        $this->assertSame(75.5, $result->totalRaw);
        $this->assertSame(7.6, $result->total);
    }

    #[Test]
    public function too_few_cards_and_invalid_cards_are_flagged_and_ignored(): void
    {
        $cards = $this->cards([
            [20, 16, 12, 8, 8, 8, 4, 4],
            [22, 15, 11, 7, 7, 8, 4, 4],
            [25, 20, 15, 10, 10, 10, 5, 5],
        ]);
        $cards[2]->is_valid = false;

        $result = (new ScoreCalculator)->calculate($cards, $this->model, new EditionSettings);

        $this->assertSame(2, $result->cardCount);
        $this->assertFalse($result->meetsMinimum());
        $this->assertTrue($result->flags()['missing_cards']);
        $this->assertSame(7.9, $result->total);
    }

    #[Test]
    public function a_card_more_than_fifteen_points_from_the_median_is_an_outlier(): void
    {
        $cards = $this->cards([
            [20, 16, 12, 8, 8, 8, 4, 4], // 80
            [22, 15, 11, 7, 7, 8, 4, 4], // 78
            [21, 17, 12, 9, 8, 8, 4, 4], // 83
            [10, 8, 5, 4, 4, 4, 2, 2],   // 39 → afwijker
        ]);

        $result = (new ScoreCalculator)->calculate($cards, $this->model, new EditionSettings(minValidCards: 4));

        $this->assertSame([$cards[3]->uuid], $result->outlierCardUuids);
        $this->assertSame([$cards[3]->uuid], $result->flags()['outliers']);
    }

    #[Test]
    public function approved_corrections_replace_the_original_scores_and_pending_ones_do_not(): void
    {
        $cards = $this->cards([
            [20, 16, 12, 8, 8, 8, 4, 4],
            [20, 16, 12, 8, 8, 8, 4, 4],
        ]);

        $approved = new ScoreCorrection(['before' => ['smaak' => 20], 'after' => ['smaak' => 10], 'status' => CorrectionStatus::Approved, 'approved_at' => now()]);
        $pending = new ScoreCorrection(['before' => ['smaak' => 20], 'after' => ['smaak' => 0], 'status' => CorrectionStatus::Pending]);
        $cards[0]->setRelation('corrections', collect([$approved]));
        $cards[1]->setRelation('corrections', collect([$pending]));

        $result = (new ScoreCalculator)->calculate($cards, $this->model, new EditionSettings(minValidCards: 2));

        $this->assertSame(15.0, $result->criterionAverages['smaak']);
        $this->assertSame(75.0, $result->totalRaw);
    }

    #[Test]
    public function no_cards_gives_zero_without_errors(): void
    {
        $result = (new ScoreCalculator)->calculate(collect(), $this->model, new EditionSettings);

        $this->assertSame(0, $result->cardCount);
        $this->assertSame(0.0, $result->total);
        $this->assertFalse($result->meetsMinimum());
    }

    /**
     * @param  list<list<int>>  $rows
     * @return Collection<int, Scorecard>
     */
    private function cards(array $rows): Collection
    {
        $codes = $this->model->criteria->pluck('code')->all();

        return collect($rows)->map(function (array $row, int $index) use ($codes): Scorecard {
            $card = new Scorecard(['uuid' => "card-{$index}", 'scores' => array_combine($codes, $row)]);
            $card->is_valid = true;
            $card->setRelation('corrections', collect());

            return $card;
        });
    }
}
