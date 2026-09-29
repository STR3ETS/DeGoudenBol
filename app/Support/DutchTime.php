<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Nederlandse tijd voor weergave en invoer; opslag blijft UTC.
 */
final class DutchTime
{
    public static function zone(): string
    {
        return (string) config('app.display_timezone', 'Europe/Amsterdam');
    }

    /**
     * Parseert een Nederlandse datum/tijd ("2026-12-21 12:00") en geeft hem terug in UTC voor opslag.
     */
    public static function toUtc(string $dutchDateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($dutchDateTime, self::zone())->utc();
    }

    /**
     * Zet een opgeslagen (UTC) tijd om naar Nederlandse tijd.
     */
    public static function display(CarbonInterface $dateTime): CarbonImmutable
    {
        return CarbonImmutable::instance($dateTime)->setTimezone(self::zone());
    }

    /**
     * Opmaak zoals "maandag 21 december 2026, 12:00".
     */
    public static function format(?CarbonInterface $dateTime, string $isoFormat = 'dddd D MMMM YYYY, HH:mm'): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return self::display($dateTime)->locale('nl')->isoFormat($isoFormat);
    }

    /**
     * Opmaak van een datum zonder tijd, zoals "21 december 2026".
     */
    public static function date(?CarbonInterface $date, string $isoFormat = 'D MMMM YYYY'): ?string
    {
        if ($date === null) {
            return null;
        }

        return CarbonImmutable::instance($date)->locale('nl')->isoFormat($isoFormat);
    }
}
