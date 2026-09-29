<?php

namespace App\Support;

/**
 * Bedragen zijn overal centen (int); btw apart. Weergave in Nederlandse notatie.
 */
final class Money
{
    public static function format(int $cents, bool $withSymbol = true): string
    {
        $formatted = number_format($cents / 100, 2, ',', '.');

        return $withSymbol ? "€ {$formatted}" : $formatted;
    }

    /**
     * Btw over een bedrag, afgerond op hele centen (half-up).
     */
    public static function vat(int $cents, float $ratePercent): int
    {
        return (int) round($cents * $ratePercent / 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function fromEuros(float|string $euros): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $euros)) * 100);
    }
}
