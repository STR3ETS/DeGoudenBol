<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * De statische pagina's van de publiekssite (CMS-export van Eazyonline, geïmporteerd met
 * tools/site-import.mjs). Eén methode per pagina, zodat routes benoemd blijven.
 */
class SiteController extends Controller
{
    /** Statische pagina's uit de export; sponsoring en goede doelen krijgen een dynamisch blok (tools/site-dynamic.mjs). */
    private const array PAGES = [
        'test' => 'site.pages.test',
        'faq' => 'site.pages.faq',
        'ondernemers' => 'site.pages.ondernemers',
        'sponsoring' => 'site.sponsoring',
        'finale' => 'site.pages.finale',
        'cadeaubonnen' => 'site.pages.cadeaubonnen',
        'goede-doelen' => 'site.goede-doelen',
        'bakkers-academie' => 'site.pages.bakkers-academie',
        'over' => 'site.pages.over',
        'privacy' => 'site.pages.privacy',
        'voorwaarden' => 'site.pages.voorwaarden',
        'voorwaarden-deelname' => 'site.pages.voorwaarden-deelname',
        'voorwaarden-testreglement' => 'site.pages.voorwaarden-testreglement',
        'voorwaarden-klachten' => 'site.pages.voorwaarden-klachten',
        'voorwaarden-merkgebruik' => 'site.pages.voorwaarden-merkgebruik',
        'voorwaarden-sponsoring' => 'site.pages.voorwaarden-sponsoring',
        'voorwaarden-goede-doelen' => 'site.pages.voorwaarden-goede-doelen',
        'voorwaarden-cadeaubonnen' => 'site.pages.voorwaarden-cadeaubonnen',
    ];

    public function page(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return view(self::PAGES[$page]);
    }

    public function deelnemen(): View
    {
        return view('site.deelnemen');
    }

    public function voorwaarden(?string $onderdeel = null): View
    {
        return $this->page($onderdeel === null ? 'voorwaarden' : 'voorwaarden-'.$onderdeel);
    }
}
