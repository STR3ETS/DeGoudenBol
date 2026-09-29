<?php

namespace App\Livewire\Public;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Support\Pdok\PdokLocatieserver;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

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

    public string $kvkNumber = '';

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

    // Stap 3: publicatie
    public string $publicName = '';

    public string $tagline = '';

    /** @var list<string> */
    public array $allergens = [];

    public string $productNotes = '';

    // Stap 4: pakket
    public ?int $packageId = null;

    // Stap 5: voorwaarden
    public bool $acceptTerms = false;

    public bool $acceptPrivacy = false;

    // Turnstile-token uit de browser (alleen als de sleutels zijn ingesteld)
    public ?string $turnstileToken = null;

    public function mount(): void
    {
        if ($this->packages->count() === 1) {
            $this->packageId = $this->packages->first()->getKey();
        }
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

        if ($this->postcode === '' || $this->houseNumber === '') {
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
                kvkNumber: $this->kvkNumber !== '' ? $this->kvkNumber : null,
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
                publicName: trim($this->publicName) ?: trim($this->companyName),
                tagline: $this->tagline !== '' ? trim($this->tagline) : null,
                allergens: array_values($this->allergens),
                productNotes: $this->productNotes !== '' ? trim($this->productNotes) : null,
                packageId: (int) $this->packageId,
                termsVersionId: $this->terms->getKey(),
                ip: request()->ip(),
                userAgent: request()->userAgent(),
            ));
        } catch (ProvinceFullException $exception) {
            $this->step = 2;
            $this->addError('provinceId', $exception->getMessage());

            return null;
        }

        $payment = $gateway->create(
            $entry->order,
            "De Gouden Bol {$edition->year} – deelname {$entry->public_name}",
            route('aanmelden.status', $entry->order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        );

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
                'kvkNumber' => ['nullable', 'digits:8'],
                'contactName' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190'],
                'phone' => ['nullable', 'string', 'max:32'],
                'website' => ['nullable', 'url', 'max:190'],
            ],
            2 => [
                'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
                'houseNumber' => ['required', 'string', 'max:16'],
                'street' => ['required', 'string', 'max:120'],
                'city' => ['required', 'string', 'max:120'],
                'provinceId' => ['required', 'integer', Rule::exists('provinces', 'id')],
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
            ],
            5 => [
                'acceptTerms' => ['accepted'],
                'acceptPrivacy' => ['accepted'],
            ],
            default => [],
        };
    }

    /**
     * Controles die verder gaan dan veldvalidatie.
     */
    protected function checkStep(int $step): void
    {
        if ($step === 1 && $this->kvkNumber !== '' && $this->edition !== null) {
            $alreadyRegistered = Entry::query()
                ->where('edition_id', $this->edition->getKey())
                ->occupyingPlace()
                ->whereHas('company', fn ($query) => $query->where('kvk_number', $this->kvkNumber))
                ->exists();

            if ($alreadyRegistered) {
                throw ValidationException::withMessages([
                    'kvkNumber' => 'Dit KvK-nummer is al aangemeld voor deze editie. Log in op het portaal of neem contact op.',
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
            'kvkNumber' => 'KvK-nummer',
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
            'acceptTerms' => 'deelnamevoorwaarden',
            'acceptPrivacy' => 'privacyverklaring',
        ];
    }
}
