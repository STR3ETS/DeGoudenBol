<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Vertaalt de navigatie-items uit config/site.php naar echte URL's.
 * Een item zonder bestaande route en zonder fallback-anker wordt weggelaten.
 */
final class SiteLinks
{
    /**
     * @param  array{label: string, route?: string|null, fallback?: string|null}  $item
     */
    public static function url(array $item): ?string
    {
        $route = $item['route'] ?? null;

        if ($route !== null && Route::has($route)) {
            return route($route);
        }

        $fallback = $item['fallback'] ?? null;

        if ($fallback !== null && Route::has('home')) {
            return route('home').$fallback;
        }

        return null;
    }

    /**
     * @param  array{label: string, route?: string|null, fallback?: string|null}  $item
     */
    public static function isCurrent(array $item): bool
    {
        $route = $item['route'] ?? null;

        return $route !== null && Route::has($route) && request()->routeIs($route, $route.'.*');
    }

    /**
     * @param  list<array{label: string, route?: string|null, fallback?: string|null}>  $items
     * @return list<array{label: string, url: string, current: bool}>
     */
    public static function resolve(array $items): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $url = self::url($item);

            if ($url === null) {
                continue;
            }

            $resolved[] = ['label' => $item['label'], 'url' => $url, 'current' => self::isCurrent($item)];
        }

        return $resolved;
    }
}
