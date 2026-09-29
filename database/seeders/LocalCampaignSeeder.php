<?php

namespace Database\Seeders;

use App\Domain\Charities\Actions\NominateCharity;
use App\Domain\Charities\Actions\ReviewCharity;
use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Models\Charity;
use App\Domain\Commerce\Actions\CreatePlacement;
use App\Domain\Commerce\Actions\CreateSponsorOrder;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Participants\Models\Entry;
use Illuminate\Database\Seeder;

/**
 * Alleen lokaal: een demo-sponsor met betaalde plaatsingen (homepage, provinciepartner, "Bakt met"),
 * een goedgekeurd goed doel voor de gepubliceerde demo-deelnemer en twee mediacontacten.
 * Idempotent: draait alleen als er nog geen sponsor is.
 */
class LocalCampaignSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || Sponsor::query()->exists()) {
            return;
        }

        $edition = Edition::current();
        $entry = Entry::query()->whereNotNull('published_at')->with(['company', 'province'])->orderBy('id')->first();

        if ($edition === null || $entry === null || Product::query()->where('edition_id', $edition->getKey())->doesntExist()) {
            return;
        }

        $sponsor = Sponsor::query()->create([
            'name' => '[DEMO] Meelfabriek De Molen',
            'url' => 'https://example.test',
            'contact_name' => '[DEMO] Contactpersoon',
            'contact_email' => 'demo-sponsor@degoudenbol.test',
            'kvk_number' => '00000000',
            'billing_address' => ['street' => 'Demostraat 2', 'postcode' => '1234 AB', 'city' => 'Haarlem'],
        ]);

        $product = fn (ProductCode $code) => Product::query()->where('edition_id', $edition->getKey())->where('code', $code)->firstOrFail();
        $period = ['starts_at' => now()->subDay(), 'ends_at' => now()->addYear()];

        $create = app(CreatePlacement::class);
        $create($sponsor, $product(ProductCode::Homepage), $period);
        $create($sponsor, $product(ProductCode::ProvincePartner), [...$period, 'province_id' => $entry->province_id]);
        $create($sponsor, $product(ProductCode::ParticipantLink), [...$period, 'entry_id' => $entry->getKey()]);

        $order = app(CreateSponsorOrder::class)($sponsor, $edition);
        app(MarkOrderPaid::class)($order);

        SponsorLink::query()->where('sponsor_id', $sponsor->getKey())->where('company_id', $entry->company_id)->update(['confirmed_by_company_at' => now()]);

        if (Charity::query()->where('edition_id', $edition->getKey())->doesntExist()) {
            $charity = app(NominateCharity::class)($entry->company, $edition, [
                'name' => '[DEMO] Stichting Buurtbakkers',
                'kvk_or_rsin' => '00000000',
                'is_anbi' => true,
                'province_id' => $entry->province_id,
                'category' => 'jeugd',
                'motivation' => 'Placeholder-motivatie voor de lokale demo.',
            ]);

            app(ReviewCharity::class)($charity, CharityStatus::Approved, array_keys(config('charities.checklist')), 'Demo-goedkeuring', null);
        }

        MediaContact::query()->firstOrCreate(['email' => 'demo-regio@degoudenbol.test'], ['name' => '[DEMO] Regioredactie', 'outlet' => '[DEMO] Regiokrant', 'province_id' => $entry->province_id]);
        MediaContact::query()->firstOrCreate(['email' => 'demo-landelijk@degoudenbol.test'], ['name' => '[DEMO] Landelijke redactie', 'outlet' => '[DEMO] Persbureau', 'province_id' => null]);
    }
}
