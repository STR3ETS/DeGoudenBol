<?php

namespace App\Http\Controllers;

use App\Support\Docs\HelpDocs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Handleiding voor beheer (/docs) en dezelfde inhoud als JSON (/docs.json) voor de hulpbot.
 * Alleen voor ingelogde medewerkers; gasten gaan naar de backoffice-login.
 */
class HelpDocsController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! auth('web')->check()) {
            return redirect('/admin/login');
        }

        return view('docs.index', ['secties' => HelpDocs::make()->sections()]);
    }

    public function json(): JsonResponse|RedirectResponse
    {
        if (! auth('web')->check()) {
            return redirect('/admin/login');
        }

        $docs = HelpDocs::make();

        return response()->json([
            'titel' => 'Handleiding beheer De Gouden Bol',
            'secties' => array_map(fn (array $section) => ['slug' => $section['slug'], 'titel' => $section['titel'], 'intro' => $section['intro']], $docs->sections()),
            'vragen' => array_map(fn (array $question) => ['id' => $question['id'], 'sectie' => $question['sectie'], 'vraag' => $question['vraag'], 'antwoord' => $question['antwoord'], 'url' => route('docs').'#'.$question['id']], $docs->questions()),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
