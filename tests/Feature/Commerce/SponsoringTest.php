<?php

namespace Tests\Feature\Commerce;

use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Actions\CreatePlacement;
use App\Domain\Commerce\Actions\CreateSponsorOrder;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Commerce\Notifications\SponsorLinkRequestedNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class SponsoringTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    private Sponsor $sponsor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();
        $this->seed(ProductSeeder::class);

        $this->entry = $this->registered('Bakkerij Een', 'een@example.test', '11111111');
        $this->sponsor = Sponsor::query()->create(['name' => 'Sponsor Test', 'url' => 'https://sponsor.example.test', 'kvk_number' => '99999999', 'billing_address' => ['street' => 'Sponsorlaan 1', 'postcode' => '1234 AB', 'city' => 'Zwolle']]);
    }

    #[Test]
    public function placements_respect_the_sales_phase_exclusivity_and_link_pricing_and_go_live_after_payment(): void
    {
        Notification::fake();

        $period = ['starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3)];
        $homepage = app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::Homepage), $period);
        $this->assertSame(PlacementStatus::Draft, $homepage->status);
        $this->assertSame(25000, $homepage->price_cents);

        // Provinciepartner is exclusief: dubbele verkoop in dezelfde periode is geblokkeerd.
        $partner = app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::ProvincePartner), [...$period, 'province_id' => $this->entry->province_id]);
        $this->assertTrue($partner->is_exclusive);

        $other = Sponsor::query()->create(['name' => 'Andere Sponsor']);

        try {
            app(CreatePlacement::class)($other, $this->product(ProductCode::ProvincePartner), [...$period, 'province_id' => $this->entry->province_id]);
            $this->fail('Dubbele verkoop had geblokkeerd moeten worden.');
        } catch (SponsoringException $exception) {
            $this->assertStringContainsString('Sponsor Test', $exception->getMessage());
        }

        // Buiten de periode mag het wel.
        app(CreatePlacement::class)($other, $this->product(ProductCode::ProvincePartner), ['starts_at' => now()->addMonths(4), 'ends_at' => now()->addMonths(6), 'province_id' => $this->entry->province_id]);

        // Landelijke toppositie pas zodra de finalisten bekend zijn.
        $this->expectExceptionMessageMatches('/finalisten/');
        app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::NationalTop), $period);
    }

    #[Test]
    public function a_participant_link_is_priced_per_link_needs_confirmation_and_shows_bakt_met_after_payment(): void
    {
        Notification::fake();

        $period = ['starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3)];
        $second = $this->registered('Bakkerij Twee', 'twee@example.test', '22222222');

        $first = app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::ParticipantLink), [...$period, 'entry_id' => $this->entry->getKey()]);
        $extra = app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::ParticipantLink), [...$period, 'entry_id' => $second->getKey()]);
        $this->assertSame(5000, $first->price_cents);
        $this->assertSame(2500, $extra->price_cents);

        $link = SponsorLink::query()->where('company_id', $this->entry->company_id)->firstOrFail();
        $this->assertTrue($link->isPending());
        Notification::assertSentTo($this->ownerOf($this->entry), SponsorLinkRequestedNotification::class);

        // Factureren: één order, regels zonder positie, 10% gereserveerd na betaling.
        app(CreatePlacement::class)($this->sponsor, $this->product(ProductCode::Homepage), $period);
        $order = app(CreateSponsorOrder::class)($this->sponsor, $this->edition);
        $this->assertSame(3, $order->lines()->count());
        $this->assertSame(32500, $order->subtotal_cents);
        $this->assertStringContainsString('Bij Bakkerij Een', $order->lines()->orderBy('sort')->first()->description);

        app(MarkOrderPaid::class)($order);

        $this->assertSame(PlacementStatus::Active, $first->fresh()->status);
        $this->assertSame('Sponsor Test', $order->fresh()->invoice->billing_name);
        $this->assertSame(3250, (int) CharityReservation::query()->where('payer_type', $this->sponsor->getMorphClass())->sum('amount_cents'));
        $this->assertNull(CharityReservation::query()->where('payer_id', $this->sponsor->getKey())->first()->regional_pot_province_id);

        // Zonder bevestiging niets op het profiel; na bevestiging "Bakt met".
        $this->get(route('bakkers.toon', $this->entry->company))->assertOk()->assertDontSee('Bakt met');

        $owner = $this->ownerOf($this->entry);
        $this->actingAs($owner, 'participant')->get(route('portaal.sponsoren', $this->entry->company))->assertOk()->assertSee('Wacht op uw beslissing')->assertSee('Sponsor Test');
        $this->actingAs($owner, 'participant')->post(route('portaal.sponsoren.reageren', [$this->entry->company, $link]), ['beslissing' => 'bevestigen'])->assertRedirect();

        $this->assertTrue($link->fresh()->isConfirmed());
        $this->get(route('bakkers.toon', $this->entry->company))->assertOk()->assertSee('Bakt met')->assertSee('Sponsor Test');

        // Homepage-sponsor en sponsorpagina.
        $this->get('/')->assertOk()->assertSee('Mede mogelijk gemaakt door')->assertSee('Sponsor Test');
        $this->get(route('sponsoren'))->assertOk()->assertSee('Sponsor Test')->assertSee('Logo op de homepage');

        // Een medewerker mag niet beslissen.
        $staff = ParticipantUser::factory()->create();
        $this->entry->company->users()->attach($staff, ['role' => CompanyUserRole::Staff->value]);
        $this->actingAs($staff, 'participant')->post(route('portaal.sponsoren.reageren', [$this->entry->company, $link]), ['beslissing' => 'weigeren'])->assertForbidden();
    }

    private function product(ProductCode $code): Product
    {
        return Product::query()->where('edition_id', $this->edition->getKey())->where('code', $code)->firstOrFail();
    }

    private function ownerOf(Entry $entry): ParticipantUser
    {
        return $entry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();
    }

    private function registered(string $name, string $email, string $kvk): Entry
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => $name, 'publicName' => $name, 'email' => $email, 'kvkNumber' => $kvk]));
        $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        return $entry;
    }
}
