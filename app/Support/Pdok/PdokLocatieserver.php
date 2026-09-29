<?php

namespace App\Support\Pdok;

use App\Domain\Edition\Models\Province;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PDOK Locatieserver (gratis overheidsdienst): postcode + huisnummer -> straat, plaats,
 * provincie en coördinaten. Resultaten worden een dag gecachet.
 */
final class PdokLocatieserver
{
    /**
     * PDOK gebruikt de Friese naam; onze provincies heten zoals in de briefing.
     */
    private const array PROVINCE_ALIASES = [
        'Fryslân' => 'friesland',
    ];

    public function __construct(private readonly string $baseUrl) {}

    public function lookup(string $postcode, string $houseNumber): ?PdokAddress
    {
        $postcode = strtoupper((string) preg_replace('/\s+/', '', $postcode));
        $houseNumber = trim($houseNumber);

        if (! preg_match('/^\d{4}[A-Z]{2}$/', $postcode) || ! preg_match('/^(\d+)/', $houseNumber, $matches)) {
            return null;
        }

        $number = $matches[1];
        $cacheKey = "pdok:{$postcode}:{$number}";

        $cached = Cache::get($cacheKey);

        if ($cached instanceof PdokAddress) {
            return $cached;
        }

        $address = $this->fetch($postcode, $number, $houseNumber);

        if ($address !== null) {
            Cache::put($cacheKey, $address, now()->addDay());
        }

        return $address;
    }

    public function provinceFor(?string $provinceName): ?Province
    {
        if ($provinceName === null) {
            return null;
        }

        $slug = self::PROVINCE_ALIASES[$provinceName] ?? null;

        return Province::query()
            ->when($slug, fn ($query) => $query->where('slug', $slug), fn ($query) => $query->where('name', $provinceName))
            ->first();
    }

    private function fetch(string $postcode, string $number, string $fullHouseNumber): ?PdokAddress
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/free", [
                    'q' => "postcode:{$postcode} AND huisnummer:{$number}",
                    'fq' => 'type:adres',
                    'rows' => 1,
                    'fl' => 'straatnaam,woonplaatsnaam,provincienaam,postcode,huisnummer,centroide_ll',
                ]);
        } catch (Throwable $exception) {
            Log::warning('PDOK niet bereikbaar', ['message' => $exception->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $doc = $response->json('response.docs.0');

        if (! is_array($doc) || blank($doc['straatnaam'] ?? null)) {
            return null;
        }

        [$lng, $lat] = $this->parsePoint($doc['centroide_ll'] ?? null);

        return new PdokAddress(
            street: (string) $doc['straatnaam'],
            city: (string) ($doc['woonplaatsnaam'] ?? ''),
            provinceName: $doc['provincienaam'] ?? null,
            lat: $lat,
            lng: $lng,
            postcode: substr($postcode, 0, 4).' '.substr($postcode, 4),
            houseNumber: $fullHouseNumber,
        );
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function parsePoint(?string $point): array
    {
        if ($point !== null && preg_match('/POINT\(([-\d.]+) ([-\d.]+)\)/', $point, $matches)) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        return [null, null];
    }
}
