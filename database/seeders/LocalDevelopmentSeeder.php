<?php

namespace Database\Seeders;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Alleen lokaal: zet de inschrijving open, publiceert placeholder-voorwaarden en maakt
 * een paar duidelijk gemarkeerde demo-inschrijvingen zodat portaal en backoffice iets tonen.
 */
class LocalDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $edition = Edition::query()->where('year', 2026)->firstOrFail();

        if ($edition->status === EditionStatus::Draft) {
            $edition->forceFill(['status' => EditionStatus::RegistrationOpen])->save();
        }

        TermsVersion::query()
            ->whereIn('type', [TermsType::Participation, TermsType::Privacy])
            ->whereNull('published_at')
            ->update(['published_at' => now()->subDay()]);

        $terms = TermsVersion::latestPublished(TermsType::Participation);
        $package = Package::query()->where('edition_id', $edition->getKey())->active()->firstOrFail();

        // Coördinaten van het stadscentrum (placeholder voor de kaart; PDOK levert ze bij een echte aanmelding).
        $demo = [
            ['[DEMO] Bakkerij Zonnebloem', 'gelderland', 'Arnhem', 'demo-zonnebloem@degoudenbol.test', true, 51.9851, 5.8987],
            ['[DEMO] Oliebollenkraam De Ketel', 'noord-holland', 'Haarlem', 'demo-ketel@degoudenbol.test', true, 52.3874, 4.6462],
            ['[DEMO] Frituur Het Hoekje', 'limburg', 'Venlo', 'demo-hoekje@degoudenbol.test', false, 51.3704, 6.1724],
        ];

        foreach ($demo as [$name, $provinceSlug, $city, $email, $paid, $lat, $lng]) {
            if (Company::query()->where('name', $name)->exists()) {
                continue;
            }

            $province = Province::query()->where('slug', $provinceSlug)->firstOrFail();

            $entry = app(RegisterEntry::class)($edition, new RegistrationData(
                companyName: $name,
                companyType: CompanyType::Bakkerij,
                kvkNumber: null,
                contactName: 'Demo Deelnemer',
                email: $email,
                phone: '0612345678',
                website: null,
                street: 'Demostraat',
                houseNumber: '1',
                postcode: '1234 AB',
                city: $city,
                provinceId: $province->getKey(),
                lat: $lat,
                lng: $lng,
                publicName: $name,
                tagline: 'Demo-inschrijving, geen echte bakker',
                allergens: ['gluten', 'melk', 'ei'],
                productNotes: null,
                packageId: $package->getKey(),
                termsVersionId: $terms->getKey(),
            ));

            if ($paid) {
                app(MarkOrderPaid::class)($entry->order);
            }
        }
    }
}
