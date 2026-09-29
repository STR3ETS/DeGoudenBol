<?php

namespace App\Support\Cdn;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare: "purge everything" op de zone. Faalt stil (met rapportage), want de applicatiecache
 * is al ververst en de CDN-TTL is kort.
 */
final class CloudflareCdnPurger implements CdnPurger
{
    public function __construct(
        private readonly string $zoneId,
        private readonly string $apiToken,
    ) {}

    public function purgeAll(): void
    {
        try {
            Http::withToken($this->apiToken)
                ->timeout(5)
                ->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache", ['purge_everything' => true])
                ->throw();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
