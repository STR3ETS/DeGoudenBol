<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StyleguideController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(config('site.styleguide_enabled'), 404);

        return view('stijlgids.index');
    }
}
