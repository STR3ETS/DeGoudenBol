<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Klikbare prototypes van het redesign (alleen met STYLEGUIDE_ENABLED): backoffice en klantportaal.
 * Demodata in de view; geen echte gegevens.
 */
class RedesignController extends Controller
{
    public function dashboard(): View
    {
        abort_unless(config('site.styleguide_enabled'), 404);

        return view('redesign.dashboard');
    }

    public function klant(): View
    {
        abort_unless(config('site.styleguide_enabled'), 404);

        return view('redesign.klant');
    }
}
