<?php

namespace Database\Seeders;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Edition\Settings\EditionSettings;
use App\Support\DutchTime;
use Database\Factories\ScoringCriterionFactory;
use Illuminate\Database\Seeder;

/**
 * Editie 2026 met de datums en instellingen uit de briefing (docs/01 en docs/07).
 * Datums met "voorstel" hangen aan besluit 1 en zijn in de backoffice aan te passen.
 */
class Edition2026Seeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::query()->updateOrCreate(
            ['year' => 2026],
            [
                'name' => 'De Gouden Bol 2026',
                'slug' => '2026',
                'status' => EditionStatus::Draft,
                'settings' => new EditionSettings(quietPeriodFrom: '2026-12-11'),
                'registration_opens_at' => DutchTime::toUtc('2026-10-12 09:00'),
                'registration_closes_at' => null,
                'first_test_day' => '2026-11-01',
                'last_test_day' => '2026-12-17',
                'freeze_at' => DutchTime::toUtc('2026-12-18 12:00'),
                'main_publication_at' => DutchTime::toUtc('2026-12-21 12:00'),
                'final_test_day' => '2026-12-23',
                'national_result_at' => DutchTime::toUtc('2026-12-29 12:00'),
                'last_redeem_day' => '2026-12-28',
            ],
        );

        $capacity = $edition->settings->capacityPerProvince;
        $revealAt = DutchTime::toUtc('2026-12-21 12:00');

        $pivotData = Province::query()->pluck('id')->mapWithKeys(fn (int $id) => [
            $id => ['capacity' => $capacity, 'reveal_at' => $revealAt],
        ])->all();

        $edition->provinces()->syncWithoutDetaching($pivotData);

        $model = ScoringModel::query()->updateOrCreate(
            ['edition_id' => $edition->id, 'product_type' => 'oliebol', 'version' => 1],
            ['name' => '100-puntenmodel oliebol 2026', 'is_active' => true],
        );

        foreach (ScoringCriterionFactory::defaultCriteria() as $criterion) {
            $model->criteria()->updateOrCreate(['code' => $criterion['code']], $criterion);
        }

        foreach (TermsType::cases() as $type) {
            TermsVersion::query()->updateOrCreate(
                ['type' => $type, 'version' => '2026.0'],
                [
                    'edition_id' => $edition->id,
                    'title' => $type->getLabel(),
                    'body' => '[TEKST VOLGT VAN DE JURIST – niet publiceren vóór ontvangst]',
                    'published_at' => null,
                ],
            );
        }
    }
}
