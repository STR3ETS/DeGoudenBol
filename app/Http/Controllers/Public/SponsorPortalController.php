<?php

namespace App\Http\Controllers\Public;

use App\Domain\Charities\Actions\NominateCharity;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Commerce\Actions\CreatePlacement;
use App\Domain\Commerce\Actions\CreateSponsorOrder;
use App\Domain\Commerce\Actions\RegisterSponsor;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\OfferAudience;
use App\Domain\Commerce\Enums\OfferStatus;
use App\Domain\Commerce\Enums\OfferType;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\StaffNotifier;
use App\Filament\Resources\SponsorOffers\SponsorOfferResource;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureSponsorSession;
use App\Support\Mail\SendOnce;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Sponsoromgeving (briefing 29 okt §12): pakket, logo, facturen, koppelingen, extra vermeldingen,
 * acties en goede doelen. Toegang via een persoonlijke beheerlink per mail; geen wachtwoord.
 */
class SponsorPortalController extends Controller
{
    /**
     * De beheerlink uit de mail: opent een sessie voor deze sponsor.
     */
    public function access(Request $request, Sponsor $sponsor): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put(EnsureSponsorSession::SESSION_KEY, $sponsor->ulid);

        return redirect()->route('sponsor.dashboard');
    }

    public function requestLink(): View
    {
        return view('site.sponsor-beheer-link');
    }

    /**
     * Altijd hetzelfde antwoord, zodat niemand kan nagaan welke adressen sponsor zijn.
     */
    public function sendLink(Request $request, SendOnce $mail): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']], [], ['email' => 'e-mailadres']);
        $email = mb_strtolower(trim($data['email']));

        Sponsor::query()->where('contact_email', $email)->each(function (Sponsor $sponsor) use ($mail, $email): void {
            $mail->send($email, new SponsorMail(SponsorMail::ACCESS, $sponsor), "sponsor:{$sponsor->getKey()}:access:".now()->format('YmdHi'));
        });

        return back()->with('status', 'Als dit adres bij een sponsor hoort, staat er binnen een paar minuten een beheerlink in de mailbox.');
    }

    public function dashboard(Request $request): View
    {
        $sponsor = $this->sponsor($request)->load(['placements.product', 'placements.province', 'placements.entry', 'links.company', 'offers', 'requestedProduct', 'requestedProvince', 'nomination']);
        $edition = Edition::current();

        return view('site.sponsor-omgeving', [
            'sponsor' => $sponsor,
            'edition' => $edition,
            'orders' => $sponsor->orders()->with(['lines', 'invoice'])->latest('id')->get(),
            'participantProduct' => $edition ? Product::query()->where('edition_id', $edition->getKey())->where('code', ProductCode::ParticipantLink)->first() : null,
            'entries' => $edition ? Entry::query()->where('edition_id', $edition->getKey())->confirmed()->with('province')->orderBy('public_name')->get(['id', 'public_name', 'province_id']) : collect(),
            'provinces' => Province::query()->orderBy('sort')->get(),
            'categories' => config('charities.categories'),
            'offerTypes' => OfferType::options(),
            'audiences' => OfferAudience::options(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $sponsor = $this->sponsor($request);

        $data = $request->validate([
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [], ['website' => 'website', 'logo' => 'logo']);

        $changes = ['url' => $data['website'] ?? $sponsor->url];

        if ($request->hasFile('logo')) {
            if ($sponsor->logo_path) {
                Storage::disk('public')->delete($sponsor->logo_path);
            }

            $changes['logo_path'] = $request->file('logo')->store('sponsors', 'public');
        }

        $sponsor->forceFill($changes)->save();

        return redirect()->route('sponsor.dashboard')->with('status', 'Uw gegevens zijn bijgewerkt.');
    }

    /**
     * Extra vermelding bij een deelnemer: tegen het lagere tarief, direct afrekenen.
     */
    public function addLink(Request $request, CreatePlacement $createPlacement, CreateSponsorOrder $createOrder, PaymentGateway $gateway): RedirectResponse
    {
        $sponsor = $this->sponsor($request);
        $edition = Edition::current() ?? abort(404);
        $product = Product::query()->where('edition_id', $edition->getKey())->where('code', ProductCode::ParticipantLink)->firstOrFail();

        $data = $request->validate([
            'entry' => ['required', 'integer', Rule::exists('entries', 'id')->where('edition_id', $edition->getKey())],
        ], [], ['entry' => 'deelnemer']);

        try {
            $order = DB::transaction(function () use ($createPlacement, $createOrder, $sponsor, $product, $edition, $data): Order {
                $createPlacement($sponsor, $product, ['entry_id' => $data['entry'], 'starts_at' => now(), 'ends_at' => RegisterSponsor::seasonEnd($edition), 'notify' => false]);
                $order = $createOrder($sponsor, $edition);
                $order->forceFill(['metadata' => ['email' => $sponsor->contact_email]])->save();

                return $order;
            });
        } catch (SponsoringException $exception) {
            return back()->withErrors(['entry' => $exception->getMessage()]);
        }

        return redirect()->away($this->checkout($order, $sponsor, $gateway));
    }

    public function pay(Request $request, Order $order, PaymentGateway $gateway): RedirectResponse
    {
        $sponsor = $this->sponsor($request);

        abort_unless($order->orderable instanceof Sponsor && $order->orderable->is($sponsor), 404);

        if (! $order->isPending()) {
            return redirect()->route('sponsor.dashboard');
        }

        return redirect()->away($this->checkout($order, $sponsor, $gateway));
    }

    public function invoice(Request $request, Invoice $invoice): View
    {
        $sponsor = $this->sponsor($request);
        $order = $invoice->order;

        abort_unless($order->orderable instanceof Sponsor && $order->orderable->is($sponsor), 404);

        return view('site.sponsor-factuur', ['invoice' => $invoice->load('order.lines', 'order.latestPayment'), 'issuer' => config('commerce.issuer')]);
    }

    public function storeOffer(Request $request, StaffNotifier $staff): RedirectResponse
    {
        $sponsor = $this->sponsor($request);
        $edition = Edition::current() ?? abort(404);

        $data = $request->validate([
            'type' => ['required', Rule::enum(OfferType::class)],
            'audience' => ['required', Rule::enum(OfferAudience::class)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
        ], [], ['type' => 'soort', 'audience' => 'doelgroep', 'title' => 'titel', 'description' => 'omschrijving']);

        $offer = $sponsor->offers()->create([...$data, 'edition_id' => $edition->getKey(), 'status' => OfferStatus::Submitted]);

        $staff->notify([StaffRole::Communication], "Sponsoractie te beoordelen: {$offer->title}", "Van {$sponsor->name}.", SponsorOfferResource::getUrl('index'), null, 'heroicon-o-gift', 'warning');

        return redirect()->route('sponsor.dashboard')->with('status', 'Bedankt! De redactie beoordeelt uw actie en mailt u de uitkomst.');
    }

    public function nominateCharity(Request $request, NominateCharity $nominate): RedirectResponse
    {
        $sponsor = $this->sponsor($request);
        $edition = Edition::current() ?? abort(404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'website' => ['nullable', 'url', 'max:255'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'category' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('charities.categories')))],
            'motivation' => ['required', 'string', 'max:2000'],
        ], [], ['name' => 'naam', 'website' => 'website', 'province_id' => 'provincie', 'category' => 'categorie', 'motivation' => 'motivatie']);

        try {
            $nominate($sponsor, $edition, $data);
        } catch (CharityException $exception) {
            return back()->withErrors(['name' => $exception->getMessage()]);
        }

        return redirect()->route('sponsor.dashboard')->with('status', 'Goed doel voorgedragen. De organisatie beslist welke doelen worden geselecteerd.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(EnsureSponsorSession::SESSION_KEY);

        return redirect()->route('sponsoren');
    }

    private function sponsor(Request $request): Sponsor
    {
        return $request->attributes->get('sponsor');
    }

    private function checkout(Order $order, Sponsor $sponsor, PaymentGateway $gateway): string
    {
        return $gateway->create(
            $order,
            "De Gouden Bol {$order->edition->year} – sponsoring {$sponsor->name}",
            route('sponsoring.status', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        )->checkout_url;
    }

    /**
     * Voor de view: status van een voorgedragen goed doel.
     */
    public static function charityLabel(?Charity $charity): ?string
    {
        return $charity?->status->getLabel();
    }
}
