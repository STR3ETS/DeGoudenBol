<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Actions\RegisterSponsor;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Http\Controllers\Controller;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Sponsor worden via de website (briefing 29 okt §8): pakket kiezen, gegevens en logo, direct
 * afrekenen voor vaste pakketten; maatwerk en de Top 10-reservering als aanvraag.
 */
class SponsorRegistrationController extends Controller
{
    public function create(Request $request): View
    {
        $edition = Edition::current();

        return view('site.sponsor-aanmelden', [
            'edition' => $edition,
            'products' => $this->products($edition),
            'provinces' => Province::query()->orderBy('sort')->get(),
            'entries' => $edition ? $this->entries($edition) : collect(),
            'selected' => $request->query('pakket'),
            'turnstileSiteKey' => app(Turnstile::class)->enabled() ? app(Turnstile::class)->siteKey() : null,
        ]);
    }

    public function store(Request $request, RegisterSponsor $register, PaymentGateway $gateway): RedirectResponse
    {
        $edition = Edition::current() ?? abort(404);
        $products = $this->products($edition);

        $data = $request->validate([
            'product' => ['required', 'integer', Rule::in($products->pluck('id')->all())],
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'kvk' => ['nullable', 'digits:8'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'street' => ['required', 'string', 'max:160'],
            'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'city' => ['required', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'entries' => ['array'],
            'entries.*' => ['integer', Rule::exists('entries', 'id')->where('edition_id', $edition->getKey())],
            'province' => ['nullable', 'integer', 'exists:provinces,id'],
            'note' => ['nullable', 'string', 'max:2000'],
            'akkoord' => ['accepted'],
        ], [], [
            'product' => 'pakket', 'company' => 'bedrijfsnaam', 'kvk' => 'KvK-nummer', 'contact_name' => 'contactpersoon',
            'email' => 'e-mailadres', 'phone' => 'telefoon', 'street' => 'straat en huisnummer', 'postcode' => 'postcode',
            'city' => 'plaats', 'website' => 'website', 'logo' => 'logo', 'entries' => 'deelnemers', 'province' => 'provincie',
            'note' => 'toelichting', 'akkoord' => 'sponsorvoorwaarden',
        ]);

        $turnstile = app(Turnstile::class);

        if ($turnstile->enabled() && ! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['akkoord' => 'De spamcontrole is niet gelukt. Probeer het opnieuw.']);
        }

        try {
            ['sponsor' => $sponsor, 'order' => $order] = $register($edition, $data, $request->file('logo'));
        } catch (SponsoringException $exception) {
            return back()->withInput()->withErrors(['product' => $exception->getMessage()]);
        }

        if ($order === null) {
            return redirect()->route('sponsoring.bedankt', $sponsor);
        }

        return redirect()->away($gateway->create(
            $order,
            "De Gouden Bol {$edition->year} – sponsoring {$sponsor->name}",
            route('sponsoring.status', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        )->checkout_url);
    }

    /**
     * Terugkeer na de checkout.
     */
    public function status(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): View
    {
        abort_unless($order->orderable instanceof Sponsor, 404);

        $payment = $order->latestPayment;

        if ($payment !== null && ! $order->isPaid() && ! $payment->status->isFinal() && $payment->provider === $gateway->name()) {
            $payment = $gateway->refresh($payment);
        }

        if ($payment?->isPaid() && ! $order->isPaid()) {
            $markPaid($order);
        }

        $order->refresh()->load('orderable', 'lines', 'latestPayment');

        return view('site.sponsor-status', ['order' => $order, 'sponsor' => $order->orderable, 'payment' => $order->latestPayment]);
    }

    public function thanks(Sponsor $sponsor): View
    {
        abort_unless($sponsor->requested_at?->isAfter(now()->subDay()), 404);

        return view('site.sponsor-status', ['order' => null, 'sponsor' => $sponsor->load('requestedProduct'), 'payment' => null]);
    }

    /**
     * Pakketten op de website. De Top 10 partner is altijd te reserveren; afrekenen volgt na de uitslag.
     *
     * @return Collection<int, Product>
     */
    private function products(?Edition $edition): Collection
    {
        if ($edition === null) {
            return collect();
        }

        return Product::query()->where('edition_id', $edition->getKey())->orderBy('sort')->get()
            ->filter(fn (Product $product) => $product->code->onWebsite())
            ->values();
    }

    /**
     * Bevestigde deelnemers voor een vermelding als deelnemerspartner: alleen openbare gegevens.
     *
     * @return Collection<int, Entry>
     */
    private function entries(Edition $edition): Collection
    {
        return Entry::query()
            ->where('edition_id', $edition->getKey())
            ->confirmed()
            ->with(['province', 'company.primaryLocation'])
            ->orderBy('public_name')
            ->get(['id', 'public_name', 'province_id', 'company_id']);
    }

    public static function availabilityLabel(Product $product): ?string
    {
        return $product->available_from_phase === AvailabilityPhase::FinalistsKnown ? 'Reserveren; afrekenen na de uitslag' : null;
    }
}
