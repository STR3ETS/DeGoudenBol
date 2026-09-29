<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Actions\ScheduleDelivery;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use App\Support\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Stap 2 uit het kernproces in het portaal: aanleverslot kiezen en het QR-aanleverbewijs.
 */
class PlanningController extends Controller
{
    public function index(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        [$company, $entry, $edition] = $this->resolve($request, $access, $bedrijf);

        $slots = $edition
            ? DeliverySlot::query()
                ->where('edition_id', $edition->getKey())
                ->with('testLocation')
                ->withCount(['entries as taken_count' => fn ($query) => $query->occupyingPlace()])
                ->where('ends_at', '>', now())
                ->orderBy('starts_at')
                ->get()
            : collect();

        return view('portal.planning.index', [
            'company' => $company,
            'entry' => $entry,
            'edition' => $edition,
            'slots' => $slots,
            'canChoose' => $entry !== null && in_array($entry->status, [EntryStatus::Registered, EntryStatus::Scheduled, EntryStatus::FreshnessExpired], true),
            'canChange' => $entry?->deliverySlot === null || $entry->deliverySlot->starts_at->isFuture(),
        ]);
    }

    public function store(Request $request, CompanyAccess $access, ScheduleDelivery $schedule, string $bedrijf): RedirectResponse
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf, [CompanyUserRole::Owner, CompanyUserRole::Staff]);

        abort_if($entry === null, 404);

        $data = $request->validate(['slot' => ['required', 'integer', 'exists:delivery_slots,id']]);
        $slot = DeliverySlot::query()->findOrFail($data['slot']);

        if ($slot->starts_at->isPast()) {
            return back()->withErrors(['slot' => 'Dit aanleverslot is al voorbij.']);
        }

        try {
            $entry = $schedule($entry, $slot);
        } catch (SlotFullException) {
            return back()->withErrors(['slot' => 'Dit aanleverslot is inmiddels vol. Kies een ander moment.']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['slot' => $exception->getMessage()]);
        }

        $request->user('participant')->notify(new DeliveryScheduledNotification($entry));

        return redirect()->route('portaal.planning', $company)->with('status', 'Uw aanleverslot is vastgelegd. Het aanleverbewijs staat hieronder klaar.');
    }

    public function proof(Request $request, CompanyAccess $access, string $bedrijf): View
    {
        [$company, $entry, $edition] = $this->resolve($request, $access, $bedrijf);

        abort_if($entry === null || $entry->delivery_code === null || $entry->deliverySlot === null, 404);

        return view('portal.planning.aanleverbewijs', [
            'company' => $company,
            'entry' => $entry->load('deliverySlot.testLocation', 'province'),
            'edition' => $edition,
            'qr' => QrCode::svg($entry->delivery_code),
        ]);
    }

    /**
     * @param  list<CompanyUserRole>  $roles
     * @return array{0: Company, 1: Entry|null, 2: Edition|null}
     */
    private function resolve(Request $request, CompanyAccess $access, ?string $bedrijf, array $roles = []): array
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, $roles);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->with('deliverySlot.testLocation')->first() : null;

        return [$company, $entry, $edition];
    }
}
