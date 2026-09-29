<?php

namespace App\Http\Controllers\Staff;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Models\Sample;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Neutraal etiket voor de labelprinter: alleen testnummer, editie en ronde.
 */
class LabelController extends Controller
{
    public function __invoke(Request $request, Sample $sample): View
    {
        abort_unless($request->user()?->hasAnyRole([StaffRole::Intake->value, StaffRole::Coordinator->value]), 403);
        abort_if($sample->sample_number === null, 404);

        if ($sample->intake && $sample->intake->label_printed_at === null) {
            $sample->intake->forceFill(['label_printed_at' => now()])->save();
        }

        return view('staff.etiket', [
            'sample' => $sample,
            'edition' => Edition::query()->find($sample->edition_id),
        ]);
    }
}
