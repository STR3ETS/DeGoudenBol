<?php

namespace App\Http\Controllers\Public;

use App\Domain\Marketing\Models\Recognition;
use App\Domain\Marketing\Services\BadgeRenderer;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Verificatie van erkenningen (/erkenning/{code}) en de badge die vanaf ons platform wordt geladen.
 */
class RecognitionController extends Controller
{
    public function lookup(Request $request): View|RedirectResponse
    {
        $code = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('code', '')) ?? '');

        if ($code !== '') {
            $recognition = Recognition::query()->where('code', $code)->first();

            return $recognition
                ? redirect()->route('erkenning.toon', $recognition)
                : redirect()->route('erkenning.zoek')->withErrors(['code' => 'Geen erkenning gevonden met deze code.']);
        }

        return view('public.erkenning.zoek');
    }

    public function show(Recognition $recognition): View
    {
        abort_if($recognition->isEmbargoed() || $recognition->isRevoked(), 404);

        $recognition->load(['company.primaryLocation', 'edition', 'province', 'entry']);

        return view('public.erkenning.toon', [
            'recognition' => $recognition,
            'historic' => $recognition->isHistoric(),
        ]);
    }

    public function badge(Request $request, Recognition $recognition, BadgeRenderer $renderer): Response
    {
        abort_if($recognition->isEmbargoed() || $recognition->isRevoked(), 404);

        $variant = in_array($request->query('variant'), BadgeRenderer::VARIANTS, true) ? $request->query('variant') : 'licht';
        $key = "badge:{$recognition->getKey()}:{$variant}:".$recognition->updated_at?->timestamp;

        $svg = Cache::remember($key, now()->addDay(), fn () => $renderer->svg($recognition, $variant));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'Content-Disposition' => 'inline; filename="de-gouden-bol-'.$recognition->type->value.'-'.$variant.'.svg"',
        ]);
    }
}
