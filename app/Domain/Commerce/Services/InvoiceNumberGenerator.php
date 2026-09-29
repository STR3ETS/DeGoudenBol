<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Doorlopende factuurnummers per jaar: 2026-0001, 2026-0002, ... Aanroepen binnen een transactie.
 *
 * @return array{year: int, sequence: int, number: string}
 */
final class InvoiceNumberGenerator
{
    public function next(int $year): array
    {
        $last = (int) Invoice::query()
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence');

        $sequence = $last + 1;

        return [
            'year' => $year,
            'sequence' => $sequence,
            'number' => sprintf('%d-%04d', $year, $sequence),
        ];
    }

    /**
     * @template T
     *
     * @param  callable(array{year: int, sequence: int, number: string}): T  $callback
     * @return T
     */
    public function withNext(int $year, callable $callback): mixed
    {
        return DB::transaction(fn () => $callback($this->next($year)));
    }
}
