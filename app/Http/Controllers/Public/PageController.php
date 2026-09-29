<?php

namespace App\Http\Controllers\Public;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TermsVersion;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Inhoudspagina's die hun feiten uit de editie-instellingen halen.
 */
class PageController extends Controller
{
    public function hoeWerktDeTest(): View
    {
        $edition = Edition::query()->current()->with('activeScoringModel.criteria')->first();

        return view('public.paginas.hoe-werkt-de-test', [
            'edition' => $edition,
            'settings' => $edition?->settings,
            'criteria' => $edition?->activeScoringModel?->criteria ?? collect(),
        ]);
    }

    public function panel(): View
    {
        $edition = Edition::query()->current()->first();

        return view('public.paginas.panel', [
            'edition' => $edition,
            'settings' => $edition?->settings,
        ]);
    }

    public function voorwaarden(): View
    {
        return $this->terms(TermsType::Participation, 'Deelnamevoorwaarden');
    }

    public function actievoorwaarden(): View
    {
        return $this->terms(TermsType::VoucherCampaign, 'Actievoorwaarden cadeaubonnen');
    }

    public function privacy(): View
    {
        return $this->terms(TermsType::Privacy, 'Privacyverklaring');
    }

    private function terms(TermsType $type, string $title): View
    {
        return view('public.paginas.voorwaarden', [
            'title' => $title,
            'terms' => TermsVersion::latestPublished($type),
        ]);
    }
}
