<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Enums\Milestone;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Models\ShareEvent;
use App\Domain\Marketing\Services\MilestoneResolver;
use App\Domain\Marketing\Services\SocialKitRenderer;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Marketing in het portaal: badges met embedcode, socialkit per mijlpaal, deelknop en download.
 */
class MarketingController extends Controller
{
    public function index(Request $request, CompanyAccess $access, MilestoneResolver $milestones, SocialKitRenderer $kit, ?string $bedrijf = null): View
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf);

        $reached = $entry ? $milestones->forEntry($entry) : collect();

        return view('portal.marketing', [
            'company' => $company,
            'entry' => $entry,
            'reached' => $reached,
            'texts' => $entry ? $reached->filter(fn (array $item) => $item['available_from'] === null)->mapWithKeys(fn (array $item) => [$item['milestone']->value => $kit->text($entry, $item['milestone'])]) : collect(),
            'formats' => $kit->formats(),
            'profileUrl' => route('bakkers.toon', $company),
            'pressRelease' => $entry ? PressRelease::query()->published()->where('edition_id', $entry->edition_id)->where('province_id', $entry->province_id)->orderByDesc('published_at')->first() : null,
        ]);
    }

    public function image(Request $request, CompanyAccess $access, MilestoneResolver $milestones, SocialKitRenderer $kit, string $bedrijf, string $milestone, string $format): Response
    {
        [, $entry] = $this->resolve($request, $access, $bedrijf);
        $milestone = Milestone::tryFrom($milestone) ?? abort(404);

        abort_if($entry === null || ! $milestones->isReached($entry, $milestone), 404);

        return response($kit->image($entry, $milestone, $format), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    public function zip(Request $request, CompanyAccess $access, MilestoneResolver $milestones, SocialKitRenderer $kit, string $bedrijf, string $milestone): Response
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf);
        $milestone = Milestone::tryFrom($milestone) ?? abort(404);

        abort_if($entry === null || ! $milestones->isReached($entry, $milestone), 404);

        ShareEvent::query()->create(['entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'milestone' => $milestone, 'kind' => 'download', 'format' => 'zip']);

        return response($kit->zip($entry, $milestone), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="de-gouden-bol-socialkit-'.$milestone->value.'.zip"',
        ]);
    }

    public function track(Request $request, CompanyAccess $access, string $bedrijf): JsonResponse
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf);

        $data = $request->validate([
            'milestone' => ['required', 'string'],
            'kind' => ['required', 'in:share,copy,download'],
            'format' => ['nullable', 'string', 'max:16'],
        ]);

        $milestone = Milestone::tryFrom($data['milestone']);

        if ($entry !== null && $milestone !== null) {
            ShareEvent::query()->create(['entry_id' => $entry->getKey(), 'company_id' => $company->getKey(), 'milestone' => $milestone, 'kind' => $data['kind'], 'format' => $data['format'] ?? null]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return array{0: Company, 1: Entry|null}
     */
    private function resolve(Request $request, CompanyAccess $access, ?string $bedrijf): array
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->with(['province', 'edition'])->first() : null;

        return [$company, $entry];
    }
}
