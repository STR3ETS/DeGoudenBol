<?php

namespace App\Livewire\Public;

use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Exceptions\VisibilitySpotTakenException;
use App\Domain\Commerce\Models\Package;
use App\Domain\Commerce\Services\VisibilitySpots;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Services\PlanningOptions;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Http\Controllers\Public\RegistrationStatusController;
use App\Support\Pdok\PdokLocatieserver;
use App\Support\Turnstile;
use App\Support\WebsiteUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Throwable;
use TypeError;

/**
 * Meerstaps aanmelding: bedrijf -> adres -> publicatie -> pakket -> voorwaarden en betaling.
 */
#[Layout('components.layouts.public')]
#[Title('Aanmelden als bakker')]
class RegistrationWizard extends Component
{
    public const int STEPS = 5;

    public int $step = 1;

    // Stap 1: bedrijf en contact
    public string $companyName = '';

    public string $companyType = '';

    public string $contactName = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    // Stap 2: adres en provincie
    public string $postcode = '';

    public string $houseNumber = '';

    public string $street = '';

    public string $city = '';

    public ?int $provinceId = null;

    public ?float $lat = null;

    public ?float $lng = null;

    public bool $lookupFailed = false;

    public bool $homeAddress = false;

    // Stap 3: publicatie
    public string $publicName = '';

    public string $tagline = '';

    /** @var list<string> */
    public array $allergens = [];

    public string $productNotes = '';

    // Stap 4: pakket en tarief (standaard, kortingsactie of openingsactie) en de extra naturel-test
    public ?int $packageId = null;

    public string $rate = 'standard';

    public string $discountProofUrl = '';

    public bool $plainAddon = false;

    public bool $pickup = false;

    // Ophalen: vrijdag en ophaallocatie direct bij de aanmelding (verzoek Gill 4 nov 2026)
    public ?int $pickupDayId = null;

    public string $pickupStreet = '';

    public string $pickupHouseNumber = '';

    public string $pickupPostcode = '';

    public string $pickupCity = '';

    public string $pickupPhone = '';

    public string $pickupInstructions = '';

    // Extra zichtbaarheid: betaalde plek in de provincie, los van de test
    public ?int $visibilityPosition = null;

    // Stap 5: voorwaarden
    public bool $acceptTerms = false;

    public bool $acceptPrivacy = false;

    // Turnstile-token uit de browser (alleen als de sleutels zijn ingesteld)
    public ?string $turnstileToken = null;

    /** Sessiesleutel voor het concept: na een reload hoeft de bakker niet alles opnieuw in te vullen. */
    public const string DRAFT_KEY = 'aanmelden.concept';

    /**
     * Velden die in het concept bewaard worden. Akkoord op voorwaarden en privacy en het Turnstile-token
     * bewust niet: die geeft de bakker bij iedere poging opnieuw.
     */
    private const array DRAFT_FIELDS = [
        'step', 'companyName', 'companyType', 'contactName', 'email', 'phone', 'website',
        'postcode', 'houseNumber', 'street', 'city', 'provinceId', 'lat', 'lng', 'homeAddress',
        'publicName', 'tagline', 'allergens', 'productNotes',
        'packageId', 'rate', 'discountProofUrl', 'plainAddon', 'pickup',
        'pickupDayId', 'pickupStreet', 'pickupHouseNumber', 'pickupPostcode', 'pickupCity', 'pickupPhone', 'pickupInstructions', 'visibilityPosition',
    ];

    private bool $draftCleared = false;

    public function mount(): void
    {
        $this->restoreDraft();

        if ($this->packageId === null && $this->packages->count() === 1) {
            $this->packageId = $this->packages->first()->getKey();
        }
    }

    /**
     * Livewire-hook aan het eind van ieder verzoek: het concept bijwerken in de sessie.
     */
    public function dehydrate(): void
    {
        if ($this->draftCleared) {
            return;
        }

        session()->put(self::DRAFT_KEY, collect(self::DRAFT_FIELDS)->mapWithKeys(fn (string $field) => [$field => $this->{$field}])->all());
    }

    private function restoreDraft(): void
    {
        $draft = session()->get(self::DRAFT_KEY);

        if (! is_array($draft)) {
            return;
        }

        foreach (self::DRAFT_FIELDS as $field) {
            if (! array_key_exists($field, $draft)) {
                continue;
            }

            try {
                $this->{$field} = $draft[$field];
            } catch (TypeError) {
                // Een oud concept met een ander type laten we vallen; de standaardwaarde blijft staan.
            }
        }

        $this->step = max(1, min($this->step, self::STEPS));

        if ($this->packageId !== null && ! $this->packages->contains('id', $this->packageId)) {
            $this->packageId = null;
        }
    }

    private function forgetDraft(): void
    {
        session()->forget(self::DRAFT_KEY);
        $this->draftCleared = true;
    }

    #[Computed]
    public function edition(): ?Edition
    {
        return Edition::query()->current()->with('provinces')->first();
    }

    #[Computed]
    public function isOpen(): bool
    {
        return $this->edition?->isRegistrationOpen() && $this->terms !== null;
    }

    /**
     * @return array<string, array{capacity: int, taken: int, available: int}>
     */
    #[Computed]
    public function availability(): array
    {
        return $this->edition ? app(ProvinceCapacity::class)->overview($this->edition) : [];
    }

    /**
     * @return Collection<int, Province>
     */
    #[Computed]
    public function provinces(): Collection
    {
        return $this->edition?->provinces ?? collect();
    }

    /**
     * @return Collection<int, Package>
     */
    #[Computed]
    public function packages(): Collection
    {
        return $this->edition
            ? Package::query()->where('edition_id', $this->edition->getKey())->active()->get()
            : collect();
    }

    #[Computed]
    public function terms(): ?TermsVersion
    {
        return TermsVersion::latestPublished(TermsType::Participation);
    }

    #[Computed]
    public function privacy(): ?TermsVersion
    {
        return TermsVersion::latestPublished(TermsType::Privacy);
    }

    #[Computed]
    public function selectedPackage(): ?Package
    {
        return $this->packages->firstWhere('id', $this->packageId);
    }

    #[Computed]
    public function selectedProvince(): ?Province
    {
        return $this->provinces->firstWhere('id', $this->provinceId);
    }

    public function discountEnabled(): bool
    {
        return (bool) $this->edition?->settings->discountEnabled() && ! $this->openingOfferEnabled();
    }

    public function openingOfferEnabled(): bool
    {
        return (bool) $this->edition?->settings->openingOfferEnabled();
    }

    /**
     * Ophaalvrijdagen om uit te kiezen, zonder aantallen; volle vrijdagen staan uit.
     *
     * @return Collection<int, PickupDay>
     */
    #[Computed]
    public function pickupDays(): Collection
    {
        return $this->edition ? app(PlanningOptions::class)->pickupDays($this->edition) : collect();
    }

    /**
     * Ophalen aangevinkt: het ophaaladres begint bij het adres uit stap 2; de bakker past het aan als de kraam
     * ergens anders staat (bijvoorbeeld bij een bouwmarkt).
     */
    public function updatedPickup(): void
    {
        if ($this->pickup && $this->pickupStreet === '') {
            $this->pickupStreet = $this->street;
            $this->pickupHouseNumber = $this->houseNumber;
            $this->pickupPostcode = $this->postcode;
            $this->pickupCity = $this->city;
        }

        if ($this->pickup && $this->pickupPhone === '') {
            $this->pickupPhone = $this->phone;
        }
    }

    /**
     * Zichtbaarheidsplekken in de gekozen provincie; bezette plekken staan uit.
     *
     * @return list<array{position: int, price_cents: int, taken: bool}>
     */
    #[Computed]
    public function visibilityOptions(): array
    {
        return $this->edition && $this->selectedProvince && $this->edition->settings->visibilitySpotsEnabled()
            ? app(VisibilitySpots::class)->options($this->edition, $this->selectedProvince)
            : [];
    }

    public function pickupEnabled(): bool
    {
        return (bool) $this->edition?->settings->pickupEnabled;
    }

    public function plainAddonEnabled(): bool
    {
        return (bool) $this->edition?->settings->plainAddonEnabled();
    }

    /**
     * De kortingsactie geldt niet zolang de openingsactie loopt; dan noemen we hem ook niet.
     */
    public function packageDescription(Package $package): string
    {
        $description = (string) $package->description;

        return $this->openingOfferEnabled()
            ? trim(str_replace('Met de kortingsactie betaalt u de helft.', '', $description))
            : $description;
    }

    public function chosenRate(): EntryRate
    {
        return match (true) {
            $this->openingOfferEnabled() => EntryRate::OpeningOffer,
            $this->wantsDiscount() => EntryRate::Discount,
            default => EntryRate::Standard,
        };
    }

    /**
     * Te betalen bij de aanmelding, met dezelfde regels als de order.
     *
     * @return array{subtotal_cents: int, total_cents: int}|null
     */
    public function orderTotals(): ?array
    {
        if ($this->selectedPackage === null || $this->edition === null) {
            return null;
        }

        return CreateOrderForEntry::totals($this->selectedPackage, $this->edition->settings, $this->edition->year, $this->chosenRate(), $this->plainAddon && $this->plainAddonEnabled(), $this->pickup && $this->pickupEnabled(), $this->visibilityPosition);
    }

    public function wantsDiscount(): bool
    {
        return $this->rate === 'discount' && $this->discountEnabled();
    }

    /**
     * Actieprijs van het gekozen pakket in centen, excl. btw.
     */
    public function discountedPriceCents(): ?int
    {
        return $this->selectedPackage && $this->edition ? $this->edition->settings->discountedCents($this->selectedPackage->price_cents) : null;
    }

    /**
     * @return array<string, string>
     */
    public function companyTypeOptions(): array
    {
        return CompanyType::options();
    }

    /**
     * @return array<string, string>
     */
    public function allergenOptions(): array
    {
        return Allergen::options();
    }

    /**
     * "www.bakkerij.nl" mag; https:// zetten we er zelf voor.
     */
    public function updatedWebsite(): void
    {
        $this->website = WebsiteUrl::normalize($this->website) ?? '';
    }

    public function updatedPostcode(): void
    {
        $this->lookupAddress();
    }

    public function updatedHouseNumber(): void
    {
        $this->lookupAddress();
    }

    public function updatedCompanyName(string $value): void
    {
        if ($this->publicName === '' || $this->publicName === $this->companyName) {
            $this->publicName = $value;
        }
    }

    public function lookupAddress(): void
    {
        $this->lookupFailed = false;

        // Pas zoeken bij een volledige postcode en een huisnummer, anders verschijnt tijdens het typen al "niet gevonden".
        if (! preg_match('/^\d{4}\s?[A-Za-z]{2}$/', trim($this->postcode)) || ! preg_match('/^\d+/', trim($this->houseNumber))) {
            return;
        }

        $address = app(PdokLocatieserver::class)->lookup($this->postcode, $this->houseNumber);

        if ($address === null) {
            $this->lookupFailed = true;

            return;
        }

        $this->street = $address->street;
        $this->city = $address->city;
        $this->postcode = $address->postcode;
        $this->lat = $address->lat;
        $this->lng = $address->lng;

        $province = app(PdokLocatieserver::class)->provinceFor($address->provinceName);

        if ($province !== null) {
            $this->provinceId = $province->getKey();
        }
    }

    public function next(): void
    {
        $this->validate($this->rulesForStep($this->step), [], $this->validationAttributes());
        $this->checkStep($this->step);

        if ($this->step === 1 && $this->publicName === '') {
            $this->publicName = $this->companyName;
        }

        $this->step = min($this->step + 1, self::STEPS);
    }

    public function back(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goTo(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    public function submit(RegisterEntry $register, PaymentGateway $gateway): ?Redirector
    {
        foreach (range(1, self::STEPS) as $step) {
            $this->validate($this->rulesForStep($step), [], $this->validationAttributes());
            $this->checkStep($step);
        }

        $edition = $this->edition;

        if ($edition === null || ! $this->isOpen) {
            $this->addError('step', 'De inschrijving is op dit moment gesloten.');

            return null;
        }

        $turnstile = app(Turnstile::class);

        if ($turnstile->enabled() && ! $turnstile->verify($this->turnstileToken, request()->ip())) {
            $this->turnstileToken = null;
            $this->addError('turnstileToken', 'De spamcontrole is niet gelukt. Vink het vakje opnieuw aan en probeer het nog eens.');

            return null;
        }

        try {
            $entry = $register($edition, new RegistrationData(
                companyName: trim($this->companyName),
                companyType: CompanyType::from($this->companyType),
                kvkNumber: null,
                contactName: trim($this->contactName),
                email: trim($this->email),
                phone: $this->phone !== '' ? trim($this->phone) : null,
                website: $this->website !== '' ? trim($this->website) : null,
                street: trim($this->street),
                houseNumber: trim($this->houseNumber),
                postcode: strtoupper(trim($this->postcode)),
                city: trim($this->city),
                provinceId: (int) $this->provinceId,
                lat: $this->lat,
                lng: $this->lng,
                homeAddress: $this->homeAddress,
                publicName: trim($this->publicName) ?: trim($this->companyName),
                tagline: $this->tagline !== '' ? trim($this->tagline) : null,
                allergens: array_values($this->allergens),
                productNotes: $this->productNotes !== '' ? trim($this->productNotes) : null,
                packageId: (int) $this->packageId,
                termsVersionId: $this->terms->getKey(),
                ip: request()->ip(),
                userAgent: request()->userAgent(),
                rate: $this->chosenRate(),
                discountProofUrl: $this->wantsDiscount() && $this->discountProofUrl !== '' ? trim($this->discountProofUrl) : null,
                plainAddon: $this->plainAddon && $this->plainAddonEnabled(),
                pickup: $this->pickup && $this->pickupEnabled(),
                visibilityPosition: $this->visibilityPosition,
                pickupPreference: $this->pickup && $this->pickupEnabled() ? [
                    'pickup_day_id' => $this->pickupDayId,
                    'street' => trim($this->pickupStreet),
                    'house_number' => trim($this->pickupHouseNumber),
                    'postcode' => strtoupper(trim($this->pickupPostcode)),
                    'city' => trim($this->pickupCity),
                    'phone' => trim($this->pickupPhone) ?: null,
                    'instructions' => trim($this->pickupInstructions) ?: null,
                ] : null,
            ));
        } catch (VisibilitySpotTakenException $exception) {
            $this->step = 4;
            $this->visibilityPosition = null;
            unset($this->visibilityOptions);
            $this->addError('visibilityPosition', $exception->getMessage());

            return null;
        } catch (ProvinceFullException $exception) {
            $this->step = 2;
            $this->addError('provinceId', $exception->getMessage());

            return null;
        }

        // De aanmelding staat; vanaf hier hoort een reload niet meer naar het ingevulde formulier te leiden.
        $this->forgetDraft();

        // Kortingsactie: eerst het bewijs laten keuren, daarna pas betalen.
        if ($entry->wantsDiscount()) {
            return $this->redirect(route('aanmelden.status', $entry->order));
        }

        // De aanmelding staat; lukt het aanmaken van de betaling niet, dan naar de statuspagina met een melding
        // (daar kan de bakker opnieuw betalen) in plaats van een serverfout. De fout gaat naar de log.
        try {
            $payment = $gateway->create(
                $entry->order,
                "De Gouden Bol {$edition->year} – deelname {$entry->public_name}",
                route('aanmelden.terug', $entry->order),
                config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null, // Stripe: webhook staat in het dashboard
            );
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('betaalfout', RegistrationStatusController::PAYMENT_UNAVAILABLE);

            return $this->redirect(route('aanmelden.status', $entry->order));
        }

        return $this->redirect($payment->checkout_url);
    }

    public function render(): View
    {
        return view('livewire.public.registration-wizard', [
            'turnstileSiteKey' => app(Turnstile::class)->enabled() ? app(Turnstile::class)->siteKey() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'companyName' => ['required', 'string', 'min:2', 'max:120'],
                'companyType' => ['required', Rule::enum(CompanyType::class)],
                'contactName' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190'],
                'phone' => ['nullable', 'string', 'max:32'],
                'website' => WebsiteUrl::rules(190),
            ],
            2 => [
                'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
                'houseNumber' => ['required', 'string', 'max:16'],
                'street' => ['required', 'string', 'max:120'],
                'city' => ['required', 'string', 'max:120'],
                'provinceId' => ['required', 'integer', Rule::exists('provinces', 'id')],
                'homeAddress' => ['boolean'],
            ],
            3 => [
                'publicName' => ['required', 'string', 'min:2', 'max:120'],
                'tagline' => ['nullable', 'string', 'max:140'],
                'allergens' => ['array'],
                'allergens.*' => [Rule::enum(Allergen::class)],
                'productNotes' => ['nullable', 'string', 'max:1000'],
            ],
            4 => [
                'packageId' => ['required', 'integer', Rule::in($this->packages->pluck('id')->all())],
                'rate' => ['required', Rule::in($this->discountEnabled() ? ['standard', 'discount'] : ['standard'])],
                'discountProofUrl' => ['nullable', 'url', 'max:500'],
                'plainAddon' => ['boolean'],
                'pickup' => ['boolean'],
                ...$this->pickupRules(),
                'visibilityPosition' => ['nullable', 'integer', Rule::in(collect($this->visibilityOptions)->reject(fn (array $option) => $option['taken'])->pluck('position')->all())],
            ],
            5 => [
                'acceptTerms' => ['accepted'],
                'acceptPrivacy' => ['accepted'],
            ],
            default => [],
        };
    }

    /**
     * Ophalen aangevinkt: vrijdag (als er een te kiezen is) en een volledig ophaaladres met telefoon voor de chauffeur.
     *
     * @return array<string, mixed>
     */
    protected function pickupRules(): array
    {
        if (! $this->pickup || ! $this->pickupEnabled()) {
            return [];
        }

        $open = $this->pickupDays->filter(fn (PickupDay $day) => $day->taken_count < $day->capacity)->pluck('id')->all();

        return [
            'pickupDayId' => $open === [] ? ['nullable'] : ['required', 'integer', Rule::in($open)],
            'pickupStreet' => ['required', 'string', 'max:120'],
            'pickupHouseNumber' => ['required', 'string', 'max:16'],
            'pickupPostcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'pickupCity' => ['required', 'string', 'max:120'],
            'pickupPhone' => ['required', 'string', 'max:40'],
            'pickupInstructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Controles die verder gaan dan veldvalidatie.
     */
    protected function checkStep(int $step): void
    {
        // Geen KvK meer (besluit Bennie 4 nov 2026: één ondernemer kan meerdere kramen hebben). Dubbel is dezelfde
        // bedrijfsnaam op dezelfde postcode in deze editie; een tweede kraam op een andere plek mag wel.
        if ($step === 2 && $this->edition !== null && $this->postcode !== '') {
            $postcode = strtoupper(str_replace(' ', '', $this->postcode));

            $alreadyRegistered = Entry::query()
                ->where('edition_id', $this->edition->getKey())
                ->occupyingPlace()
                ->whereHas('company', fn ($query) => $query
                    ->whereRaw('lower(trim(name)) = ?', [mb_strtolower(trim($this->companyName))])
                    ->whereHas('primaryLocation', fn ($location) => $location->whereRaw("upper(replace(postcode, ' ', '')) = ?", [$postcode])))
                ->exists();

            if ($alreadyRegistered) {
                throw ValidationException::withMessages([
                    'postcode' => "{$this->companyName} is op deze postcode al aangemeld voor deze editie. Log in op het portaal of neem contact op.",
                ]);
            }
        }

        if ($step === 2 && $this->selectedProvince !== null) {
            $available = $this->availability[$this->selectedProvince->slug]['available'] ?? 0;

            if ($available <= 0) {
                throw ValidationException::withMessages([
                    'provinceId' => "Alle plekken in {$this->selectedProvince->name} zijn bezet.",
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'companyName' => 'bedrijfsnaam',
            'companyType' => 'type bedrijf',
            'contactName' => 'contactpersoon',
            'email' => 'e-mailadres',
            'phone' => 'telefoonnummer',
            'website' => 'website',
            'postcode' => 'postcode',
            'houseNumber' => 'huisnummer',
            'street' => 'straat',
            'city' => 'plaats',
            'provinceId' => 'provincie',
            'publicName' => 'naam op de site',
            'tagline' => 'korte omschrijving',
            'allergens' => 'allergenen',
            'productNotes' => 'toelichting',
            'packageId' => 'pakket',
            'rate' => 'tarief',
            'discountProofUrl' => 'link naar uw post',
            'pickupDayId' => 'ophaalvrijdag',
            'visibilityPosition' => 'zichtbaarheidsplek',
            'pickupStreet' => 'straat van de ophaallocatie',
            'pickupHouseNumber' => 'huisnummer van de ophaallocatie',
            'pickupPostcode' => 'postcode van de ophaallocatie',
            'pickupCity' => 'plaats van de ophaallocatie',
            'pickupPhone' => 'telefoon voor de chauffeur',
            'pickupInstructions' => 'toelichting',
            'acceptTerms' => 'deelnamevoorwaarden',
            'acceptPrivacy' => 'privacyverklaring',
        ];
    }
}
