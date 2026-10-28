<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Site\LiveData;
use Illuminate\Http\JsonResponse;

/**
 * /live.json: nieuwe deelnemers en de Voorlijst per provincie voor de zwevende widget op de site.
 */
class LiveDataController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(LiveData::get(), 200, [
            'Cache-Control' => 'public, max-age=60',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
