<?php

namespace App\Http\Controllers\Public;

use App\Domain\Marketing\Models\NewsPost;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('public.nieuws.index', [
            'posts' => NewsPost::query()->published()->paginate(12),
        ]);
    }

    public function show(NewsPost $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('public.nieuws.show', ['post' => $post]);
    }
}
