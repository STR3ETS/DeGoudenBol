<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Site\MachineContent;
use Illuminate\Contracts\View\View;

/**
 * Blogs en landingspagina's van de contentmachine (zie MachineContent). De pagina's hebben hun
 * eigen kopblok en secties in de opmaak van de site, dus hier komt alleen de omlijsting omheen.
 */
class ContentController extends Controller
{
    public function __construct(private readonly MachineContent $content) {}

    public function index(): View
    {
        return view('site.blog', ['artikelen' => $this->content->artikelen()]);
    }

    public function artikel(string $slug): View
    {
        $pagina = $this->content->artikel($slug);

        abort_unless($pagina !== null, 404);

        return view('site.content', ['pagina' => $pagina, 'schema' => 'Article']);
    }

    public function landingspagina(string $slug): View
    {
        $pagina = $this->content->landingspagina($slug);

        abort_unless($pagina !== null, 404);

        return view('site.content', ['pagina' => $pagina, 'schema' => 'WebPage']);
    }
}
