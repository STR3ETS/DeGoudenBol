<?php

namespace App\Support;

use App\Support\Cdn\CdnPurger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Paginacache van de publiekssite: één generatienummer voor alle pagina's, verhoogd bij iedere
 * wijziging aan publieke inhoud. Publicatie ververst daarnaast de losse rankingsleutels.
 */
final class PublicCache
{
    public const int TTL_MINUTES = 10;

    private const string GENERATION_KEY = 'public:generation';

    private static bool $cdnDirty = false;

    public static function enabled(): bool
    {
        return (bool) config('publiccache.enabled', true);
    }

    public static function ttlMinutes(): int
    {
        return max(1, (int) config('publiccache.ttl_minutes', self::TTL_MINUTES));
    }

    public static function generation(): int
    {
        return (int) Cache::get(self::GENERATION_KEY, 1);
    }

    /**
     * Laat alle gecachte pagina's vervallen en markeert het CDN voor een purge (aan het eind van het request).
     */
    public static function bump(): void
    {
        Cache::forever(self::GENERATION_KEY, self::generation() + 1);
        self::$cdnDirty = true;
    }

    public static function pageKey(Request $request): string
    {
        return 'public:page:'.self::generation().':'.sha1($request->getHost().$request->getRequestUri());
    }

    /**
     * Koppelt de invalidatie aan modelwijzigingen: opslaan of verwijderen verhoogt de generatie.
     *
     * @param  list<class-string<Model>>  $models
     */
    public static function bumpOn(array $models): void
    {
        foreach ($models as $model) {
            $model::saved(fn () => self::bump());
            $model::deleted(fn () => self::bump());
        }
    }

    public static function purgeCdnIfDirty(): void
    {
        if (! self::$cdnDirty) {
            return;
        }

        self::$cdnDirty = false;
        app(CdnPurger::class)->purgeAll();
    }

    public static function provinceKey(int $editionId, int $provinceId): string
    {
        return "public:ranking:{$editionId}:{$provinceId}";
    }

    public static function forgetProvince(int $editionId, int $provinceId): void
    {
        Cache::forget(self::provinceKey($editionId, $provinceId));
        Cache::forget("public:home:{$editionId}");
        self::bump();
    }

    public static function forgetEdition(int $editionId): void
    {
        Cache::forget("public:home:{$editionId}");
        self::bump();
    }
}
