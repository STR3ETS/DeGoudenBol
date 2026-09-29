<?php

namespace App\Domain\Vouchers\Services;

use App\Domain\Edition\Models\Province;
use App\Domain\Vouchers\Models\Voucher;

/**
 * Leesbaar bonnummer (GB26-GLD-7K3M) en het losse 128-bit QR-token, dat alleen gehasht wordt bewaard.
 */
final class VoucherCode
{
    private const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const array ABBREVIATIONS = [
        'DR' => 'DRE', 'FL' => 'FLE', 'FR' => 'FRI', 'GE' => 'GLD', 'GR' => 'GRO', 'LI' => 'LIM',
        'NB' => 'NBR', 'NH' => 'NHO', 'OV' => 'OVE', 'UT' => 'UTR', 'ZE' => 'ZEE', 'ZH' => 'ZHO',
    ];

    public function generate(Province $province, int $year): string
    {
        $prefix = strtoupper((string) config('vouchers.code_prefix', 'GB')).substr((string) $year, -2);
        $abbreviation = self::ABBREVIATIONS[strtoupper($province->code)] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $province->name) ?? 'NLD', 0, 3));

        do {
            $code = "{$prefix}-{$abbreviation}-".$this->random(4);
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @return array{plain: string, hash: string}
     */
    public function token(): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

        return ['plain' => $plain, 'hash' => $this->hash($plain)];
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Handmatige invoer: "gb26 gld 7k3m" wordt GB26-GLD-7K3M.
     */
    public function normalizeCode(string $input): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');

        if (! preg_match('/^([A-Z]{2}\d{2})([A-Z]{3})([A-Z0-9]{4})$/', $clean, $parts)) {
            return null;
        }

        return "{$parts[1]}-{$parts[2]}-{$parts[3]}";
    }

    /**
     * Haalt het token uit een gescande QR (volledige URL of alleen het token).
     */
    public function tokenFromScan(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('#/bon/([A-Za-z0-9_-]{16,64})#', $input, $matches)) {
            return $matches[1];
        }

        return preg_match('/^[A-Za-z0-9_-]{16,64}$/', $input) ? $input : null;
    }

    private function random(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
