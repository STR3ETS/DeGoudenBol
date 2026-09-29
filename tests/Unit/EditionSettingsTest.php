<?php

namespace Tests\Unit;

use App\Domain\Edition\Enums\CharityBasis;
use App\Domain\Edition\Enums\ParticipationModel;
use App\Domain\Edition\Settings\EditionSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EditionSettingsTest extends TestCase
{
    #[Test]
    public function defaults_follow_the_briefing_advice(): void
    {
        $settings = new EditionSettings;

        $this->assertSame(50, $settings->capacityPerProvince);
        $this->assertSame(10, $settings->provincialListLength);
        $this->assertSame(5, $settings->nationalListLength);
        $this->assertSame(5.0, $settings->publishThreshold);
        $this->assertSame(6, $settings->minValidCards);
        $this->assertSame(8, $settings->maxSamplesPerSession);
        $this->assertSame(180, $settings->freshnessWindowMinutes);
        $this->assertSame(15, $settings->outlierDeviationPoints);
        $this->assertSame(ParticipationModel::B, $settings->participationModel);
        $this->assertSame(CharityBasis::Received, $settings->charityBasis);
        $this->assertSame(['smaak', 'structuur_luchtigheid', 'versheid', 'vulling_verhouding'], $settings->tieBreakOrder);
    }

    #[Test]
    public function it_round_trips_through_snake_case_arrays_with_enums_as_values(): void
    {
        $original = new EditionSettings(
            capacityPerProvince: 10,
            participationModel: ParticipationModel::A,
            quietPeriodFrom: '2026-12-11',
        );

        $array = $original->toArray();

        $this->assertSame(10, $array['capacity_per_province']);
        $this->assertSame('A', $array['participation_model']);
        $this->assertSame('2026-12-11', $array['quiet_period_from']);

        $restored = EditionSettings::fromArray($array);

        $this->assertEquals($original, $restored);
    }

    #[Test]
    public function unknown_keys_are_ignored_and_missing_keys_keep_their_defaults(): void
    {
        $settings = EditionSettings::fromArray(['national_list_length' => 10, 'onbekend' => 'x']);

        $this->assertSame(10, $settings->nationalListLength);
        $this->assertSame(50, $settings->capacityPerProvince);
    }

    #[Test]
    public function with_returns_a_changed_copy(): void
    {
        $settings = new EditionSettings;

        $changed = $settings->with(['publishThreshold' => 6.5, 'min_valid_cards' => 5]);

        $this->assertSame(5.0, $settings->publishThreshold);
        $this->assertSame(6.5, $changed->publishThreshold);
        $this->assertSame(5, $changed->minValidCards);
    }
}
