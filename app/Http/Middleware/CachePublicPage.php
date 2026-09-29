<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Volledige paginacache voor anonieme bezoekers van de publiekssite. Alleen benoemde routes uit
 * config/publiccache.php; nooit voor ingelogde gebruikers, formulierfouten of flashberichten.
 */
class CachePublicPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->applies($request)) {
            return $next($request);
        }

        $key = PublicCache::pageKey($request);
        $cached = Cache::get($key);

        if (is_array($cached) && isset($cached['content'])) {
            return $this->decorate(response($cached['content'], 200, $cached['headers'] ?? []), 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() !== 200 || ! is_string($response->getContent()) || ! str_contains((string) $response->headers->get('Content-Type'), 'html') && ! str_contains((string) $response->headers->get('Content-Type'), 'xml')) {
            return $response;
        }

        Cache::put($key, [
            'content' => $response->getContent(),
            'headers' => ['Content-Type' => $response->headers->get('Content-Type')],
        ], now()->addMinutes(PublicCache::ttlMinutes()));

        return $this->decorate($response, 'MISS');
    }

    private function applies(Request $request): bool
    {
        if (! PublicCache::enabled() || ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        $route = $request->route()?->getName();

        if ($route === null || ! in_array($route, (array) config('publiccache.routes', []), true)) {
            return false;
        }

        if (auth()->guard('web')->check() || auth()->guard('participant')->check()) {
            return false;
        }

        if ($request->hasSession() && ($request->session()->has('errors') || $request->session()->has('nieuwsbrief') || $request->session()->has('status'))) {
            return false;
        }

        return true;
    }

    private function decorate(Response $response, string $state): Response
    {
        $seconds = PublicCache::ttlMinutes() * 60;

        $response->headers->set('X-Public-Cache', $state);
        $response->headers->set('Cache-Control', "public, max-age=0, s-maxage={$seconds}");

        return $response;
    }
}
