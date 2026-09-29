<?php

namespace Tests\Support;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\CompanyType;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\PackageSeeder;
use Database\Seeders\ProvinceSeeder;

/**
 * Gedeelde opzet voor tests rond de aanmeldflow: editie 2026 met open inschrijving,
 * basispakket en gepubliceerde deelnamevoorwaarden.
 */
trait BuildsRegistrations
{
    protected Edition $edition;

    protected Package $package;

    protected TermsVersion $terms;

    protected function setUpRegistrationWorld(): void
    {
        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class, PackageSeeder::class]);

        $this->edition = Edition::query()->where('year', 2026)->firstOrFail();
        $this->edition->forceFill(['status' => EditionStatus::RegistrationOpen])->save();

        $this->package = Package::query()->where('edition_id', $this->edition->getKey())->firstOrFail();

        $this->terms = TermsVersion::factory()->ofType(TermsType::Participation)->published()->create([
            'edition_id' => $this->edition->getKey(),
            'version' => '2026.1',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function registrationData(array $overrides = []): RegistrationData
    {
        $province = $overrides['province'] ?? Province::query()->where('slug', 'gelderland')->firstOrFail();

        return new RegistrationData(
            companyName: $overrides['companyName'] ?? 'Bakkerij Testers',
            companyType: $overrides['companyType'] ?? CompanyType::Bakkerij,
            kvkNumber: $overrides['kvkNumber'] ?? '12345678',
            contactName: $overrides['contactName'] ?? 'Tessa Tester',
            email: $overrides['email'] ?? 'tessa@example.test',
            phone: '0612345678',
            website: null,
            street: 'Bakkersstraat',
            houseNumber: '12a',
            postcode: '6811 AB',
            city: 'Arnhem',
            provinceId: $province->getKey(),
            lat: 51.98,
            lng: 5.91,
            publicName: $overrides['publicName'] ?? 'Bakkerij Testers',
            tagline: 'Sinds kort de lekkerste van de straat',
            allergens: ['gluten', 'melk'],
            productNotes: null,
            packageId: $overrides['packageId'] ?? $this->package->getKey(),
            termsVersionId: $this->terms->getKey(),
            ip: '127.0.0.1',
            userAgent: 'PHPUnit',
        );
    }
}
