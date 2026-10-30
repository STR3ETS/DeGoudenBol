<?php

namespace App\Support\Portal;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Testing\Enums\SampleRound;
use Illuminate\Support\Collection;

/**
 * Leesmodel voor het officiële beoordelingsrapport (naar het aangeleverde rapportsjabloon): de
 * onderdelen met score en maximum, de totaalscore op 100 punten en de toelichting van het panel.
 * De scores komen uit de gekoppelde uitslag, niet uit de ranking; het rapport bestaat dus al vóór
 * de landelijke publicatie.
 */
final readonly class PersonalReport
{
    /**
     * @param  list<array{name: string, score: float|null, max: int}>  $criteria
     */
    private function __construct(
        public Entry $entry,
        public ConfidentialReport $report,
        public array $criteria,
        public ?float $totalPoints,
        public ?float $grade,
        public ?int $cardCount,
    ) {}

    public static function for(Entry $entry): ?self
    {
        $entry->loadMissing(['company.primaryLocation', 'edition', 'confidentialReport', 'publicationItems']);
        $report = $entry->confidentialReport;

        if ($report === null || ! $report->isPublished()) {
            return null;
        }

        /** @var PublicationItem|null $item */
        $item = $entry->publicationItems
            ->filter(fn (PublicationItem $item) => $item->round === SampleRound::Provincial)
            ->sortByDesc('id')
            ->first();

        $snapshot = $item?->result_snapshot ?? [];
        $averages = $snapshot['criterion_averages'] ?? [];

        $model = ScoringModel::query()
            ->where('edition_id', $entry->edition_id)
            ->when(isset($snapshot['scoring_model_version']), fn ($query) => $query->where('version', $snapshot['scoring_model_version']), fn ($query) => $query->where('is_active', true))
            ->with('criteria')
            ->first();

        /** @var Collection<int, array{name: string, score: float|null, max: int}> $criteria */
        $criteria = ($model?->criteria ?? collect())->map(fn ($criterion) => [
            'name' => $criterion->name,
            'score' => isset($averages[$criterion->code]) ? round((float) $averages[$criterion->code], 1) : null,
            'max' => $criterion->max_points,
        ]);

        return new self(
            entry: $entry,
            report: $report,
            criteria: $criteria->values()->all(),
            totalPoints: isset($snapshot['total_raw']) ? round((float) $snapshot['total_raw'], 1) : null,
            grade: isset($snapshot['total']) ? (float) $snapshot['total'] : null,
            cardCount: isset($snapshot['card_count']) ? (int) $snapshot['card_count'] : null,
        );
    }

    public function city(): ?string
    {
        return $this->entry->company->primaryLocation?->city;
    }

    public static function number(?float $value): string
    {
        return $value === null ? '–' : rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }
}
