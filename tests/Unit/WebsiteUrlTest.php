<?php

namespace Tests\Unit;

use App\Support\WebsiteUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Een website zoals mensen hem intypen, zonder https:// (verzoek Bennie 3 nov 2026).
 */
class WebsiteUrlTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function inputs(): array
    {
        return [
            'met www' => ['www.bakkerij.nl', 'https://www.bakkerij.nl'],
            'kaal domein' => ['bakkerij.nl', 'https://bakkerij.nl'],
            'met pad' => ['bakkerij.nl/oliebollen', 'https://bakkerij.nl/oliebollen'],
            'met spaties' => ['  www.bakkerij.nl  ', 'https://www.bakkerij.nl'],
            'al https' => ['https://bakkerij.nl', 'https://bakkerij.nl'],
            'http blijft http' => ['http://bakkerij.nl', 'http://bakkerij.nl'],
            'hoofdletters in schema' => ['HTTPS://Bakkerij.nl', 'https://Bakkerij.nl'],
            'leeg' => ['', null],
            'niets' => [null, null],
        ];
    }

    #[Test]
    #[DataProvider('inputs')]
    public function it_adds_https_when_it_is_missing(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, WebsiteUrl::normalize($input));
    }
}
