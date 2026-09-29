<?php

namespace App\Domain\Intake\Services;

use App\Domain\Participants\Models\Entry;

/**
 * Leesbare aanlevercode (ook de inhoud van de QR op het aanleverbewijs): 8 tekens zonder
 * verwarrende letters, geschreven als XXXX-XXXX.
 */
final class DeliveryCode
{
    private const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        do {
            $code = $this->random();
        } while (Entry::query()->where('delivery_code', $code)->exists());

        return $code;
    }

    /**
     * Maakt van handmatige invoer ("k7m3 q9zd", een gescande QR) de opgeslagen vorm.
     */
    public function normalize(string $input): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');

        if (strlen($clean) !== 8) {
            return null;
        }

        return substr($clean, 0, 4).'-'.substr($clean, 4, 4);
    }

    private function random(): string
    {
        $chars = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < 8; $i++) {
            $chars .= self::ALPHABET[random_int(0, $max)];
        }

        return substr($chars, 0, 4).'-'.substr($chars, 4, 4);
    }
}
