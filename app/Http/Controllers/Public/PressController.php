<?php

namespace App\Http\Controllers\Public;

use App\Domain\Marketing\Models\PressRelease;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Pers: gepubliceerde persberichten, en de embargo-perskit via een ondertekende tijdelijke link.
 */
class PressController extends Controller
{
    public function index(): View
    {
        return view('public.pers.index', [
            'releases' => PressRelease::query()->published()->with('province')->orderByDesc('published_at')->get(),
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
