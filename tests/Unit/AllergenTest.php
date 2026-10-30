<?php

namespace Tests\Unit;

use App\Domain\Participants\Enums\Allergen;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AllergenTest extends TestCase
{
    #[Test]
    public function the_list_follows_the_bakery_list_of_30_october(): void
    {
        $this->assertSame(
            ['Tarwe / granen', 'Ei', 'Melk (incl. lactose)', "Pinda's / noten", 'Sesamzaad', 'Soja', 'Mosterd', 'Lupine', 'Sulfiet'],
            array_values(Allergen::options()),
        );
    }

    #[Test]
    public function retired_codes_are_translated_or_dropped(): void
    {
        $this->assertSame(['gluten', 'noten'], Allergen::normalize(['gluten', 'pinda', 'noten', 'vis', 'selderij', 'onbekend']));
    }
}
