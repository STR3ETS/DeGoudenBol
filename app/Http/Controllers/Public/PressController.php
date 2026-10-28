<?php

namespace App\Http\Controllers\Public;

use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Marketing\Models\PressRelease;
use App\Http\Controllers\Controller;
use App\Support\Site\SiteFacts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Pers: gepubliceerde persberichten, en de embargo-perskit via een ondertekende tijdelijke link.
 */
class PressController extends Controller
{
    public function index(): View
    {
        $edition = Edition::query()->current()->first();

        return view('site.pers', [
            'releases' => PressRelease::query()->published()->with('province')->orderByDesc('published_at')->get(),
            'posts' => NewsPost::query()->published()->orderByDesc('published_at')->limit(12)->get(),
            'startLabel' => SiteFacts::startLabel($edition),
        ]);
    }

    public function show(PressRelease $pressRelease): View
    {
        abort_unless($pressRelease->isPublished(), 404);

        return view('public.pers.show', ['release' => $pressRelease->load('province'), 'embargoed' => false]);
    }

    public function kit(PressRelease $pressRelease): View|RedirectResponse
    {
        if ($pressRelease->isPublished()) {
            return redirect()->route('pers.toon', $pressRelease);
        }

        return view('public.pers.show', ['release' => $pressRelease->load('province'), 'embargoed' => true]);
    }
}
