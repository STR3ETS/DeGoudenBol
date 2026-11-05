<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Province;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Livewire\Public\RegistrationWizard;
use App\Support\DutchTime;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class RegistrationWizardTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();

        Http::fake([
            'api.pdok.nl/*' => Http::response([
                'response' => ['docs' => [[
                    'straatnaam' => 'Bakkersstraat',
                    'woonplaatsnaam' => 'Arnhem',
                    'provincienaam' => 'Gelderland',
                    'postcode' => '6811AB',
                    'huisnummer' => 12,
                    'centroide_ll' => 'POINT(5.91 51.98)',
                ]]],
            ]),
        ]);
    }

    #[Test]
    public function the_page_shows_a_closed_message_when_registration_is_not_open(): void
    {
        // Vóór de openingsdatum: nog niet open, met de datum.
        $this->edition->forceFill(['status' => EditionStatus::Draft, 'registration_opens_at' => now()->addDays(3)])->save();

        $this->get(route('aanmelden'))
            ->assertOk()
            ->assertSee('nog niet open')
            ->assertSee(DutchTime::format(now()->addDays(3), 'dddd D MMMM YYYY'));

        // Na de openingsdatum opent de inschrijving vanzelf, ook zonder de status aan te raken.
        $this->edition->forceFill(['registration_opens_at' => now()->subHour()])->save();
        $this->get(route('aanmelden'))->assertOk()->assertDontSee('nog niet open')->assertSee('officieel keuren');

        // Ook in de testfase blijft inschrijven mogelijk tot de sluitingsdatum.
        $this->edition->forceFill(['status' => EditionStatus::Testing, 'registration_closes_at' => now()->addDay()])->save();
        $this->get(route('aanmelden'))->assertOk()->assertDontSee('nog niet open');

        // Na de sluitingsdatum: gesloten.
        $this->edition->forceFill(['registration_closes_at' => now()->subMinute()])->save();
        $this->get(route('aanmelden'))->assertOk()->assertSee('is gesloten', false)->assertDontSee('nog niet open');

        // Zonder sluitingsdatum sluit de inschrijving aan het eind van de laatste testdag.
        $this->edition->forceFill(['registration_closes_at' => null, 'last_test_day' => now()->subDays(2)->toDateString()])->save();
        $this->get(route('aanmelden'))->assertOk()->assertSee('is gesloten', false);

        // Bevroren of gepubliceerd: altijd dicht, wat de datums ook zeggen.
        $this->edition->forceFill(['status' => EditionStatus::Frozen, 'registration_closes_at' => now()->addDay(), 'last_test_day' => now()->addDays(10)->toDateString()])->save();
        $this->get(route('aanmelden'))->assertOk()->assertSee('is gesloten', false);
    }

    #[Test]
    public function step_one_validates_the_company_details_in_dutch(): void
    {
        Livewire::test(RegistrationWizard::class)
            ->set('companyName', 'B')
            ->set('email', 'geen-email')
            ->call('next')
            ->assertHasErrors(['companyName', 'companyType', 'contactName', 'email'])
            ->assertSet('step', 1)
            ->assertSee('e-mailadres');
    }

    #[Test]
    public function the_address_is_completed_via_pdok(): void
    {
        $gelderland = Province::query()->where('slug', 'gelderland')->firstOrFail();

        Livewire::test(RegistrationWizard::class)
            ->set('step', 2)
            ->set('houseNumber', '12')
            ->set('postcode', '681')
            ->assertSet('lookupFailed', false)
            ->assertSet('street', '')
            ->set('postcode', '6811ab')
            ->assertSet('street', 'Bakkersstraat')
            ->assertSet('city', 'Arnhem')
            ->assertSet('postcode', '6811 AB')
            ->assertSet('provinceId', $gelderland->getKey())
            ->assertSet('lat', 51.98)
            ->assertDontSee('plekken beschikbaar');
    }

    #[Test]
    public function a_full_province_blocks_the_second_step(): void
    {
        $zeeland = Province::query()->where('slug', 'zeeland')->firstOrFail();
        DB::table('edition_province')->where('edition_id', $this->edition->getKey())->where('province_id', $zeeland->getKey())->update(['capacity' => 0]);

        Livewire::test(RegistrationWizard::class)
            ->set('step', 2)
            ->set('postcode', '4331 AA')
            ->set('houseNumber', '1')
            ->set('street', 'Markt')
            ->set('city', 'Middelburg')
            ->set('provinceId', $zeeland->getKey())
            ->call('next')
            ->assertHasErrors(['provinceId'])
            ->assertSet('step', 2);
    }

    #[Test]
    public function the_same_business_on_the_same_postcode_cannot_register_twice_but_a_second_stand_can(): void
    {
        // Geen KvK meer in het formulier (besluit Bennie 4 nov 2026); één ondernemer kan meerdere kramen hebben.
        app(RegisterEntry::class)($this->edition, $this->registrationData());

        $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->assertDontSee('KvK-nummer')
            ->call('next')
            ->assertSet('step', 2)
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->call('next')
            ->assertHasErrors(['postcode'])
            ->assertSet('step', 2);

        session()->forget(RegistrationWizard::DRAFT_KEY);
        Http::fake(['api.pdok.nl/*' => Http::response(['response' => ['docs' => []]])]);

        $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->call('next')
            ->set('postcode', '8011 AA')
            ->set('houseNumber', '1')
            ->set('street', 'Markt')
            ->set('city', 'Zwolle')
            ->set('provinceId', Province::query()->where('slug', 'overijssel')->value('id'))
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 3);
    }

    #[Test]
    public function completing_all_steps_registers_the_entry_and_redirects_to_the_checkout(): void
    {
        $component = $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->call('next')
            ->assertSet('step', 2)
            ->assertSet('publicName', 'Bakkerij Testers')
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->call('next')
            ->assertSet('step', 3)
            ->set('tagline', 'Sinds kort de lekkerste van de straat')
            ->set('allergens', ['gluten', 'melk'])
            ->call('next')
            ->assertSet('step', 4)
            ->assertSet('packageId', $this->package->getKey())
            ->call('next')
            ->assertSet('step', 5)
            ->assertSee('Bakkerij Testers')
            ->assertSee('€ 181,50')
            ->call('submit')
            ->assertHasErrors(['acceptTerms', 'acceptPrivacy'])
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $entry = Entry::query()->firstOrFail();

        $this->assertSame(EntryStatus::PendingPayment, $entry->status);
        $this->assertSame('gelderland', $entry->province->slug);
        $this->assertSame(['gluten', 'melk'], $entry->allergens);
        $this->assertSame(51.98, $entry->company->primaryLocation->lat);

        $payment = $entry->order->latestPayment;
        $this->assertNotNull($payment);
        $component->assertRedirect(route('betaling.fake', $payment));
    }

    #[Test]
    public function a_failing_payment_provider_keeps_the_registration_and_offers_a_retry_instead_of_a_server_error(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $mock) => $mock->shouldReceive('create')->andThrow(new RuntimeException('Stripe weigert de sleutel'))->shouldReceive('name')->andReturn('stripe'));

        $component = $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $entry = Entry::query()->firstOrFail();
        $component->assertRedirect(route('aanmelden.status', $entry->order));
        $this->assertSame(EntryStatus::PendingPayment, $entry->status);
        $this->assertNull($entry->order->latestPayment);

        $this->get(route('aanmelden.status', $entry->order))->assertOk()->assertSee('Het betaalsysteem is op dit moment niet bereikbaar')->assertSee('Betalen');

        $this->post(route('aanmelden.opnieuw', $entry->order))
            ->assertRedirect(route('aanmelden.status', $entry->order))
            ->assertSessionHas('betaalfout');
    }

    #[Test]
    public function a_reload_keeps_what_the_baker_filled_in_until_the_registration_is_sent(): void
    {
        $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->call('next')
            ->assertSet('step', 2)
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->set('acceptTerms', true);

        // Reload: een nieuwe component in dezelfde sessie.
        Livewire::test(RegistrationWizard::class)
            ->assertSet('step', 2)
            ->assertSet('companyName', 'Bakkerij Testers')
            ->assertSet('email', 'tessa@example.test')
            ->assertSet('city', 'Arnhem')
            ->assertSet('acceptTerms', false)
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNotNull(Entry::query()->first());
        $this->assertFalse(session()->has(RegistrationWizard::DRAFT_KEY));

        Livewire::test(RegistrationWizard::class)->assertSet('step', 1)->assertSet('companyName', '');
    }

    #[Test]
    public function a_home_address_shows_only_the_city_on_the_public_profile(): void
    {
        Livewire::test(RegistrationWizard::class)->set('step', 2)->assertSee('vul dan gewoon uw huisadres in');

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['homeAddress' => true]));
        $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        $this->assertTrue($entry->company->primaryLocation->hide_address);
        $this->get(route('bakkers.toon', $entry->company))
            ->assertOk()
            ->assertSee('Arnhem')
            ->assertDontSee('Bakkersstraat')
            ->assertDontSee('6811 AB')
            ->assertDontSee('google.com/maps', false)
            ->assertDontSee('GeoCoordinates', false);
    }

    #[Test]
    public function a_website_without_https_is_accepted_and_completed(): void
    {
        $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->assertSee('www.uwbakkerij.nl')
            ->set('website', 'www.bakkerij-testers.nl')
            ->assertSet('website', 'https://www.bakkerij-testers.nl')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        // Nieuw begin, zonder het bewaarde concept van hierboven.
        session()->forget(RegistrationWizard::DRAFT_KEY);

        $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->set('website', 'bakkerij')
            ->call('next')
            ->assertHasErrors(['website'])
            ->assertSet('step', 1);
    }

    #[Test]
    public function choosing_pickup_asks_for_the_friday_and_pickup_location_without_counts(): void
    {
        $friday = PickupDay::query()->create([
            'edition_id' => $this->edition->getKey(),
            'date' => DutchTime::display(now())->next(CarbonInterface::FRIDAY)->addWeek()->toDateString(),
            'capacity' => 30,
        ]);

        $component = $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->set('step', 4)
            ->set('pickup', true)
            ->assertSee('Ophaallocatie')
            ->assertSee($friday->label())
            ->assertDontSee('plekken vrij')
            ->assertSet('pickupStreet', 'Bakkersstraat')
            ->assertSet('pickupCity', 'Arnhem')
            ->call('next')
            ->assertHasErrors(['pickupDayId'])
            ->set('pickupDayId', $friday->getKey())
            ->set('pickupInstructions', 'Bij de Intratuin')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 5)
            ->assertSee('Bij de Intratuin');

        $this->assertSame($friday->getKey(), $component->get('pickupDayId'));
    }

    private function completeStepOne(Testable $component): Testable
    {
        return $component
            ->set('companyName', 'Bakkerij Testers')
            ->set('companyType', 'bakkerij')
            ->set('contactName', 'Tessa Tester')
            ->set('email', 'tessa@example.test')
            ->set('phone', '0612345678');
    }
}
