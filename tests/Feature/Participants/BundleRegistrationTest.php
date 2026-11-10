<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Edition\Models\Province;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use App\Livewire\Public\RegistrationWizard;
use App\Support\DutchTime;
use App\Support\Site\LiveData;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Besluiten Bennie 9 en 10 nov 2026: één deelnamepakket (krenten, naturel en ophalen) zonder keuzes,
 * landelijk maximaal 150 deelnemers en nergens aantallen richting bezoekers.
 */
class BundleRegistrationTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
        $this->edition->forceFill(['settings' => $this->edition->settings->with([
            'bundle_cents' => 19500,
            'bundle_regular_cents' => 39000,
            'opening_offer_cents' => 7500,
            'plain_addon_cents' => 5000,
        ])])->save();

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
    public function the_bundle_is_one_order_line_that_includes_the_plain_test_and_the_pickup(): void
    {
        // Wat de aanmelder ook meestuurt: het pakket bepaalt tarief, naturel en ophalen.
        $entry = app(RegisterEntry::class)($this->edition->fresh(), $this->registrationData(['rate' => EntryRate::Discount, 'plainAddon' => false, 'pickup' => false]));

        $this->assertSame(EntryRate::Bundle, $entry->rate);
        $this->assertTrue($entry->plain_addon);
        $this->assertTrue($entry->pickup_prepaid);
        $this->assertFalse($entry->wantsDiscount());

        $lines = $entry->order->lines;
        $this->assertCount(1, $lines);
        $this->assertSame(19500, $lines->first()->unit_price_cents);
        $this->assertSame(CreateOrderForEntry::bundleDescription(2026), $lines->first()->description);
        $this->assertSame(23595, $entry->order->total_cents); // EUR 195 + 21% btw
    }

    #[Test]
    public function the_wizard_shows_one_package_without_choices_and_asks_where_to_pick_up(): void
    {
        $friday = PickupDay::query()->create([
            'edition_id' => $this->edition->getKey(),
            'date' => DutchTime::display(now())->next(CarbonInterface::FRIDAY)->addWeek()->toDateString(),
            'capacity' => 30,
        ]);

        $component = Livewire::test(RegistrationWizard::class)
            ->set('companyName', 'Bakkerij Testers')
            ->set('companyType', 'bakkerij')
            ->set('contactName', 'Tessa Tester')
            ->set('email', 'tessa@example.test')
            ->set('phone', '0612345678')
            ->call('next')
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->call('next')
            ->call('next')
            ->assertSet('step', 4)
            ->assertSee('Deelnamepakket De Gouden Bol 2026')
            ->assertSee('Test van uw naturel oliebol')
            ->assertSee('€ 195,00')
            ->assertSee('€ 390,00')
            ->assertSee('Introductieactie 50% korting')
            ->assertSee('Filmpjes.')
            ->assertDontSee('Openingsactie')
            ->assertDontSee('Test ook uw naturel oliebol')
            ->assertDontSee('Indien ophalen')
            ->assertDontSee('Extra zichtbaarheid')
            ->assertDontSee('Actieprijs')
            ->assertSet('pickupStreet', 'Bakkersstraat')
            ->call('next')
            ->assertHasErrors(['pickupDayId'])
            ->set('pickupDayId', $friday->getKey())
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 5)
            ->assertSee('€ 235,95')
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $entry = Entry::query()->firstOrFail();

        $this->assertSame(EntryRate::Bundle, $entry->rate);
        $this->assertSame(23595, $entry->order->total_cents);
        $this->assertSame($friday->getKey(), $entry->pickup_preference['pickup_day_id']);
        $component->assertRedirect(route('betaling.fake', $entry->order->latestPayment));
    }

    #[Test]
    public function the_video_is_an_optional_extra_that_is_added_to_the_package_price(): void
    {
        Notification::fake();

        $this->edition->forceFill(['settings' => $this->edition->settings->with(['video_addon_cents' => 10000])])->save();

        $component = Livewire::test(RegistrationWizard::class)
            ->set('companyName', 'Bakkerij Testers')
            ->set('companyType', 'bakkerij')
            ->set('contactName', 'Tessa Tester')
            ->set('email', 'tessa@example.test')
            ->set('phone', '0612345678')
            ->call('next')
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->call('next')
            ->call('next')
            ->assertSet('step', 4)
            ->assertSeeInOrder(['Deelnamepakket De Gouden Bol 2026', 'Laat bij het ophalen een kort filmpje maken', 'Ophaallocatie'])
            ->assertSee('+ € 100,00')
            ->assertDontSee('Filmpjes.')
            ->assertSet('videoAddon', false);

        // Niet aangevinkt: alleen het pakket.
        $this->assertSame(23595, $component->instance()->orderTotals()['total_cents']);

        $component->set('videoAddon', true)
            ->call('next')
            ->assertSet('step', 5)
            ->assertSee('+ filmpje')
            ->assertSee('€ 356,95')
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $entry = Entry::query()->firstOrFail();
        $video = $entry->order->lines->firstWhere('lineable_type', CreateOrderForEntry::VIDEO_ADDON_LINE_TYPE);

        $this->assertTrue($entry->video_addon);
        $this->assertSame(10000, $video->unit_price_cents);
        $this->assertSame(35695, $entry->order->total_cents); // (EUR 195 + EUR 100) + 21% btw

        app(MarkOrderPaid::class)($entry->order);

        $this->actingAs($entry->company->owners()->first(), 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertOk()
            ->assertSee('Filmpje')
            ->assertSee('€ 100,00 excl. btw');
    }

    #[Test]
    public function without_a_price_the_video_is_not_offered_and_never_charged(): void
    {
        $entry = app(RegisterEntry::class)($this->edition->fresh(), $this->registrationData(['videoAddon' => true]));

        $this->assertFalse($entry->video_addon);
        $this->assertCount(1, $entry->order->lines);
        $this->assertSame(23595, $entry->order->total_cents);

        Livewire::test(RegistrationWizard::class)->set('step', 4)->assertDontSee('+ € 100,00')->assertSee('Filmpjes.');
    }

    #[Test]
    public function the_national_maximum_closes_every_province_and_shows_full_without_counts(): void
    {
        Notification::fake();

        $this->edition->forceFill(['settings' => $this->edition->settings->with(['total_capacity' => 2])])->save();
        $edition = $this->edition->fresh();

        $first = app(RegisterEntry::class)($edition, $this->registrationData());
        app(MarkOrderPaid::class)($first->order);

        Livewire::test(RegistrationWizard::class)->assertDontSee('De inschrijving is')->assertSee('Laat uw oliebollen');

        app(RegisterEntry::class)($edition, $this->registrationData([
            'email' => 'tweede@example.test',
            'companyName' => 'Tweede',
            'publicName' => 'Tweede',
            'province' => Province::query()->where('slug', 'drenthe')->firstOrFail(),
        ]));

        Livewire::test(RegistrationWizard::class)->assertSee('De inschrijving is')->assertSee('vol')->assertDontSee('Laat uw oliebollen');

        $this->get(route('deelnemen'))->assertOk()->assertSee('De inschrijving is vol.')->assertDontSee('Start je aanmelding');

        $this->expectException(ProvinceFullException::class);

        app(RegisterEntry::class)($edition, $this->registrationData([
            'email' => 'derde@example.test',
            'companyName' => 'Derde',
            'publicName' => 'Derde',
            'province' => Province::query()->where('slug', 'zeeland')->firstOrFail(),
        ]));
    }

    #[Test]
    public function the_live_widget_lists_every_participant(): void
    {
        Notification::fake();

        foreach (range(1, 8) as $number) {
            $entry = app(RegisterEntry::class)($this->edition->fresh(), $this->registrationData([
                'email' => "bakker{$number}@example.test",
                'companyName' => "Bakker {$number}",
                'publicName' => "Bakker {$number}",
            ]));
            app(MarkOrderPaid::class)($entry->order);
        }

        $this->assertCount(8, LiveData::build()['signups']);

        // De widget toont de hele lijst en noemt geen aantal bij de knop.
        $script = file_get_contents(public_path('site/assets/f09daa7f7315e68a8b4af036abab52ea88446eb7.js'));
        $this->assertStringNotContainsString('signups.slice(0,6)', $script);
        $this->assertStringNotContainsString("+' aanmeldingen'", $script);
    }
}
