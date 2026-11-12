<?php

namespace App\Filament\Support;

use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Exceptions\VisibilitySpotTakenException;
use App\Domain\Commerce\Models\Package;
use App\Domain\Commerce\Services\VisibilitySpots;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use App\Support\DutchTime;
use App\Support\Money;
use App\Support\Pdok\PdokLocatieserver;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

/**
 * Aanmelding met betaallink (verzoek Bennie, 12 nov 2026): de organisatie vult de gegevens van de bakker in en
 * kiest wat hij afneemt; de bakker krijgt één link, gaat daar akkoord met de voorwaarden en betaalt.
 * Geen aparte betaalstroom: het is een gewone aanmelding met een langere reservering, zodat factuur,
 * bevestiging, portaal en planning daarna precies zo lopen als bij een aanmelding via de site.
 */
final class PaymentLinkActions
{
    public const int DEFAULT_VALID_DAYS = 14;

    public const int MAX_VALID_DAYS = 60;

    /**
     * Wie een betaallink mag aanmaken en inzien: de rollen die de deelnemer bij naam kennen en met geld werken.
     */
    public static function allows(mixed $user): bool
    {
        return $user instanceof User
            && $user->is_active
            && $user->hasAnyRole([StaffRole::Admin->value, StaffRole::Intake->value, StaffRole::Finance->value]);
    }

    public static function url(Entry $entry): ?string
    {
        return $entry->order ? route('aanmelden.status', $entry->order) : null;
    }

    /**
     * Knop boven de lijst met inschrijvingen: gegevens invullen, aanmelding klaarzetten, link delen.
     */
    public static function create(): Action
    {
        return Action::make('createWithPaymentLink')
            ->label('Aanmelding met betaallink')
            ->icon(Heroicon::OutlinedLink)
            ->visible(fn (): bool => self::allows(auth()->user()) && Edition::current() !== null)
            ->modalHeading('Aanmelding met betaallink')
            ->modalDescription('Vul de gegevens van de bakker in en kies wat hij afneemt. Je krijgt een link die je kunt delen; de bakker hoeft alleen nog akkoord te gaan met de voorwaarden en te betalen.')
            ->modalSubmitActionLabel('Aanmaken en link tonen')
            ->modalWidth(Width::ThreeExtraLarge)
            ->schema(fn (): array => self::schema(Edition::current()))
            ->action(function (array $data, RegisterEntry $register, PdokLocatieserver $pdok): void {
                $edition = Edition::current();
                $terms = TermsVersion::latestPublished(TermsType::Participation);
                $package = $edition ? Package::query()->where('edition_id', $edition->getKey())->active()->orderBy('sort')->first() : null;

                if ($edition === null || $terms === null || $package === null) {
                    self::fail('Aanmaken kan nog niet', 'Er is geen lopende editie, geen actief deelnamepakket of er zijn nog geen gepubliceerde deelnamevoorwaarden.');

                    return;
                }

                $companyName = trim((string) $data['company_name']);
                $postcode = strtoupper((string) preg_replace('/\s+/', '', (string) $data['postcode']));

                if (self::alreadyRegistered($edition, $companyName, $postcode)) {
                    self::fail('Deze bakker is al aangemeld', "{$companyName} staat op deze postcode al in de inschrijvingen van deze editie.");

                    return;
                }

                // Coördinaten voor de kaart: uit het opzoeken bij het invullen, anders nu nog een keer proberen.
                $address = blank($data['lat'] ?? null) ? $pdok->lookup($postcode, (string) $data['house_number']) : null;
                $validDays = (int) ($data['valid_days'] ?? self::DEFAULT_VALID_DAYS);

                try {
                    $entry = $register($edition, new RegistrationData(
                        companyName: $companyName,
                        companyType: $data['company_type'] instanceof CompanyType ? $data['company_type'] : CompanyType::from((string) $data['company_type']),
                        kvkNumber: null,
                        contactName: trim((string) $data['contact_name']),
                        email: trim((string) $data['email']),
                        phone: filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
                        website: null,
                        street: trim((string) $data['street']),
                        houseNumber: trim((string) $data['house_number']),
                        postcode: $postcode,
                        city: trim((string) $data['city']),
                        provinceId: (int) $data['province_id'],
                        lat: filled($data['lat'] ?? null) ? (float) $data['lat'] : $address?->lat,
                        lng: filled($data['lng'] ?? null) ? (float) $data['lng'] : $address?->lng,
                        publicName: filled($data['public_name'] ?? null) ? trim((string) $data['public_name']) : $companyName,
                        tagline: null,
                        allergens: [],
                        productNotes: null,
                        packageId: $package->getKey(),
                        termsVersionId: $terms->getKey(),
                        rate: EntryRate::Standard,
                        plainAddon: (bool) ($data['plain_addon'] ?? false),
                        pickup: (bool) ($data['pickup'] ?? false),
                        visibilityPosition: filled($data['visibility_position'] ?? null) ? (int) $data['visibility_position'] : null,
                        videoAddon: (bool) ($data['video_addon'] ?? false),
                        termsAccepted: false,
                        reservationMinutes: $validDays * 24 * 60,
                    ));
                } catch (ProvinceFullException|VisibilitySpotTakenException $exception) {
                    self::fail('Aanmaken is niet gelukt', $exception->getMessage());

                    return;
                }

                $url = self::url($entry);

                Notification::make()
                    ->title("Betaallink voor {$entry->company->name}")
                    ->body("{$url}<br><br>Totaal {$entry->order->formattedTotal()} incl. btw. Geldig tot ".DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY, HH:mm').'. Je vindt de link later terug bij de inschrijving, onder "Betaallink".')
                    ->success()
                    ->persistent()
                    ->actions([
                        Action::make('copyPaymentLink')
                            ->label('Kopieer link')
                            ->button()
                            ->alpineClickHandler('window.navigator.clipboard.writeText('.Js::from($url).'); $tooltip('.Js::from('Gekopieerd').', { theme: $store.theme, timeout: 2000 })'),
                        Action::make('openPaymentLink')
                            ->label('Bekijk de betaalpagina')
                            ->link()
                            ->url($url, shouldOpenInNewTab: true),
                    ])
                    ->send();
            });
    }

    /**
     * Knop bij een inschrijving die nog betaald moet worden: de link opnieuw tonen om te kopiëren.
     */
    public static function show(): Action
    {
        return Action::make('paymentLink')
            ->label('Betaallink')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->visible(fn (Entry $record): bool => self::allows(auth()->user()) && $record->canPay() && (bool) $record->order?->isPending())
            ->modalHeading(fn (Entry $record): string => "Betaallink voor {$record->public_name}")
            ->modalDescription(fn (Entry $record): string => "Totaal {$record->order->formattedTotal()} incl. btw. Geldig tot ".DutchTime::format($record->reservation_expires_at, 'D MMMM YYYY, HH:mm').'. Daarna vervalt de aanmelding en komt de plek vrij.')
            ->fillForm(fn (Entry $record): array => ['link' => self::url($record)])
            ->schema([
                TextInput::make('link')->label('Link om te delen')->readOnly()->copyable(copyMessage: 'Gekopieerd'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten');
    }

    /**
     * @return list<Section>
     */
    private static function schema(?Edition $edition): array
    {
        $settings = $edition?->settings;
        $bundle = (bool) $settings?->bundleEnabled();

        return [
            Section::make('Bedrijf')->columns(2)->schema([
                TextInput::make('company_name')->label('Bedrijfsnaam')->required()->maxLength(120),
                Select::make('company_type')->label('Type')->options(CompanyType::class)->required(),
                TextInput::make('public_name')->label('Naam op de site')->maxLength(120)
                    ->helperText('Leeg laten = de bedrijfsnaam.'),
            ]),
            Section::make('Contactpersoon')->columns(2)->schema([
                TextInput::make('contact_name')->label('Naam')->required()->maxLength(120),
                TextInput::make('phone')->label('Telefoon')->tel()->maxLength(32),
                TextInput::make('email')->label('E-mailadres')->email()->required()->maxLength(190)->columnSpanFull()
                    ->helperText('Hier gaan de bevestiging, de factuur en de inloglink voor het portaal naartoe.'),
            ]),
            Section::make('Adres van de zaak of kraam')
                ->description('Na postcode en huisnummer vullen straat, plaats en provincie zichzelf in.')
                ->columns(2)
                ->schema([
                    TextInput::make('postcode')->label('Postcode')->required()->regex('/^\d{4}\s?[A-Za-z]{2}$/')
                        ->live(onBlur: true)->afterStateUpdated(fn (Get $get, Set $set) => self::lookupAddress($get, $set)),
                    TextInput::make('house_number')->label('Huisnummer')->required()->maxLength(16)
                        ->live(onBlur: true)->afterStateUpdated(fn (Get $get, Set $set) => self::lookupAddress($get, $set)),
                    TextInput::make('street')->label('Straat')->required()->maxLength(120),
                    TextInput::make('city')->label('Plaats')->required()->maxLength(120),
                    Select::make('province_id')->label('Provincie')->required()->live()
                        ->options(fn (): array => Province::query()->orderBy('sort')->pluck('name', 'id')->all()),
                    Hidden::make('lat'),
                    Hidden::make('lng'),
                ]),
            Section::make('Wat neemt de bakker af')
                ->description($bundle && $edition
                    ? CreateOrderForEntry::bundleDescription($edition->year).' · '.Money::format($settings->bundleCents).' excl. btw'
                    : 'De deelname tegen het tarief dat nu op de site geldt.')
                ->columns(2)
                ->schema([
                    Toggle::make('plain_addon')->label('Test naturel oliebol'.($settings?->plainAddonEnabled() ? ' (+ '.Money::format($settings->plainAddonCents).' excl. btw)' : ''))
                        ->visible(! $bundle && (bool) $settings?->plainAddonEnabled()),
                    Toggle::make('pickup')->label('Ophalen op vrijdag'.($settings ? ' (+ '.Money::format($settings->pickupFeeCents).' excl. btw)' : ''))
                        ->live()
                        ->visible(! $bundle && (bool) $settings?->pickupEnabled),
                    Toggle::make('video_addon')->label('Filmpje bij het ophalen'.($settings?->videoAddonEnabled() ? ' (+ '.Money::format($settings->videoAddonCents).' excl. btw)' : ''))
                        ->visible(fn (Get $get): bool => (bool) $settings?->videoAddonEnabled() && ($bundle || (bool) $get('pickup')))
                        ->columnSpanFull(),
                    Select::make('visibility_position')->label('Extra zichtbaarheid: uitgelichte plek in de provincie')
                        ->placeholder('Geen')
                        ->options(fn (Get $get): array => self::visibilityOptions($edition, $get('province_id')))
                        ->visible((bool) $settings?->visibilitySpotsEnabled())
                        ->helperText('Optioneel. Kies eerst de provincie. De plek blijft bezet zolang de betaallink geldig is.')
                        ->columnSpanFull(),
                    TextInput::make('valid_days')->label('Link geldig (dagen)')->numeric()->integer()->required()
                        ->default(self::DEFAULT_VALID_DAYS)->minValue(1)->maxValue(self::MAX_VALID_DAYS)
                        ->helperText('Zo lang blijft de plek voor deze bakker vast. Niet betaald binnen die tijd: de aanmelding vervalt.'),
                ]),
        ];
    }

    private static function lookupAddress(Get $get, Set $set): void
    {
        $pdok = app(PdokLocatieserver::class);
        $address = $pdok->lookup((string) $get('postcode'), (string) $get('house_number'));

        if ($address === null) {
            return;
        }

        $set('street', $address->street);
        $set('city', $address->city);
        $set('postcode', $address->postcode);
        $set('lat', $address->lat);
        $set('lng', $address->lng);

        if ($province = $pdok->provinceFor($address->provinceName)) {
            $set('province_id', $province->getKey());
        }
    }

    /**
     * @return array<int, string>
     */
    private static function visibilityOptions(?Edition $edition, mixed $provinceId): array
    {
        $province = filled($provinceId) ? Province::query()->find($provinceId) : null;

        if ($edition === null || $province === null) {
            return [];
        }

        return collect(app(VisibilitySpots::class)->options($edition, $province))
            ->reject(fn (array $option): bool => $option['taken'])
            ->mapWithKeys(fn (array $option): array => [$option['position'] => "Plek {$option['position']} · ".Money::format($option['price_cents']).' excl. btw'])
            ->all();
    }

    /**
     * Zelfde regel als de aanmeldwizard: dezelfde bedrijfsnaam op dezelfde postcode in deze editie is dubbel.
     */
    private static function alreadyRegistered(Edition $edition, string $companyName, string $postcode): bool
    {
        return Entry::query()
            ->where('edition_id', $edition->getKey())
            ->occupyingPlace()
            ->whereHas('company', fn ($query) => $query
                ->whereRaw('lower(trim(name)) = ?', [mb_strtolower($companyName)])
                ->whereHas('primaryLocation', fn ($location) => $location->whereRaw("upper(replace(postcode, ' ', '')) = ?", [$postcode])))
            ->exists();
    }

    private static function fail(string $title, string $body): void
    {
        Notification::make()->title($title)->body($body)->danger()->persistent()->send();
    }
}
