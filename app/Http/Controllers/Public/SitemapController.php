<?php

namespace App\Http\Controllers\Public;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Participants\Models\Entry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function (): string {
            $urls = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('provincies.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('bakkers.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
                ['loc' => route('hoe-werkt-de-test'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('panel'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('nieuws.index'), 'priority' => '0.6', 'changefreq' => 'weekly'],
                ['loc' => route('aanmelden'), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['loc' => route('voorwaarden'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ];

            foreach (Province::query()->orderBy('sort')->get() as $province) {
                $urls[] = ['loc' => route('provincies.show', $province), 'priority' => '0.9', 'changefreq' => 'daily'];
            }

            $edition = Edition::query()->current()->first();

            if ($edition) {
                Entry::query()->where('edition_id', $edition->getKey())->confirmed()->with('company')->each(function (Entry $entry) use (&$urls): void {
                    $urls[] = ['loc' => route('bakkers.toon', $entry->company), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $entry->company->updated_at?->toAtomString()];
                });
            }

            NewsPost::query()->published()->each(function (NewsPost $post) use (&$urls): void {
                $urls[] = ['loc' => route('nieuws.toon', $post), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $post->published_at?->toAtomString()];
            });

            $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

            foreach ($urls as $url) {
                $lines[] = '  <url><loc>'.e($url['loc']).'</loc>'
                    .(isset($url['lastmod']) ? '<lastmod>'.e($url['lastmod']).'</lastmod>' : '')
                    .'<changefreq>'.$url['changefreq'].'</changefreq><priority>'.$url['priority'].'</priority></url>';
            }

            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
