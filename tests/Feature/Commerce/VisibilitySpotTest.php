<?php

namespace Tests\Feature\Commerce;

use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Actions\ReserveVisibilitySpot;
use App\Domain\Commerce\Exceptions\VisibilitySpotTakenException;
use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Commerce\Notifications\VisibilitySpotConfirmedNotification;
use App\Domain\Commerce\Services\VisibilitySpots;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use App\Livewire\Public\RegistrationWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Betaalde zichtbaarheidsplekken (verzoek 6 nov 2026): plek 1 t/m 10 per provincie, elke plek één keer,
 * te kiezen bij het afrekenen en in het portaal. Puur marketing, los van de test.
 */
class VisibilitySpotTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private const array PRICES = [95000, 87200, 79400, 71700, 63900, 56100, 48300, 40600, 32800, 25000];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
        $this->edition->forceFill(['settings' => $this->edition->settings->with(['visibility_spot_prices_cents' => self::PRICES])])->save();
    }

    #[Test]
    public function the_introduction_prices_add_up_to_six_thousand_per_province(): void
    {
        $this->assertSame(600000, array_sum(self::PRICES));
        $this->assertSame(95000, $this->edition->fresh()->settings->visibilitySpotPrice(1));
        $this->assertSame(25000, $this->edition->fresh()->settings->visibilitySpotPrice(10));
        $this->assertNull($this->edition->fresh()->settings->visibilitySpotPrice(11));
    }

    #[Test]
    public function a_spot_bought_at_registration_is_on_the_order_and_goes_live_after_payment(): void
    {
        Notification::fake();

        $entry = $this->register(['visibilityPosition' => 3]);

        $spot = $entry->visibilitySpot;
        $this->assertSame(3, $spot->position);
        $this->assertSame(79400, $spot->price_cents);
        $this->assertFalse($spot->isPaid());
        $this->assertSame(79400, $entry->order->lines->firstWhere('lineable_type', CreateOrderForEntry::VISIBILITY_LINE_TYPE)->subtotal_cents);
        $this->assertSame(18150 + 96074, $entry->order->total_cents); // EUR 150 + EUR 794, beide + 21% btw

        $this->get(route('provincies.show', 'gelderland'))->assertOk()->assertDontSee('Uitgelicht in Gelderland');

        app(MarkOrderPaid::class)($entry->order);

        $this->assertTrue($spot->fresh()->isPaid());
        $this->assertSame((int) now()->year, $spot->fresh()->ends_at->setTimezone('Europe/Amsterdam')->year);
        Notification::assertSentTo($entry->company->owners()->first(), VisibilitySpotConfirmedNotification::class);

        $this->get(route('provincies.show', 'gelderland'))
            ->assertOk()
            ->assertSee('Uitgelicht in Gelderland')
            ->assertSee('Betaalde vermelding')
            ->assertSee('Bakkerij Testers');
    }

    #[Test]
    public function every_spot_can_only_be_sold_once_and_a_taken_spot_rolls_the_registration_back(): void
    {
        $this->register(['visibilityPosition' => 1]);

        try {
            $this->register(['visibilityPosition' => 1, 'companyName' => 'Tweede', 'publicName' => 'Tweede', 'email' => 'tweede@example.test']);
            $this->fail('Dezelfde plek hoort niet twee keer te kunnen.');
        } catch (VisibilitySpotTakenException) {
        }

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(1, VisibilitySpot::query()->count());

        // Andere provincie: daar is plek 1 gewoon vrij.
        $this->register(['visibilityPosition' => 1, 'companyName' => 'Derde', 'publicName' => 'Derde', 'email' => 'derde@example.test', 'province' => Province::query()->where('slug', 'zeeland')->firstOrFail()]);
        $this->assertSame(2, VisibilitySpot::query()->count());
    }

    #[Test]
    public function an_expired_reservation_frees_the_spot_again(): void
    {
        $first = $this->register(['visibilityPosition' => 2]);
        $second = $this->register(['companyName' => 'Tweede', 'publicName' => 'Tweede', 'email' => 'tweede@example.test']);

        $this->travel(2)->hours();

        $spot = app(ReserveVisibilitySpot::class)($second, 2, now()->addHour());

        $this->assertSame($second->getKey(), $spot->entry_id);
        $this->assertNull($first->visibilitySpot()->first());
    }

    #[Test]
    public function the_wizard_no_longer_offers_spots_they_are_bought_in_the_portal(): void
    {
        $component = Livewire::test(RegistrationWizard::class)
            ->set('provinceId', Province::query()->where('slug', 'gelderland')->value('id'))
            ->set('packageId', $this->package->getKey())
            ->set('step', 4)
            ->assertDontSee('Extra zichtbaarheid')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 5);

        $this->assertSame(18150, $component->instance()->orderTotals()['total_cents']);
    }

    #[Test]
    public function a_new_spot_is_paid_now_but_only_shows_from_the_main_publication(): void
    {
        Notification::fake();

        $sold = $this->register(['visibilityPosition' => 1]);
        app(MarkOrderPaid::class)($sold->order);

        $publication = now()->addDays(10)->startOfHour();
        $this->edition->forceFill([
            'main_publication_at' => $publication,
            'settings' => $this->edition->settings->with([
                'visibility_spots_start_at_publication' => true,
                'visibility_spot_regular_prices_cents' => [225000, 195000],
            ]),
        ])->save();

        $entry = $this->register(['companyName' => 'Tweede', 'publicName' => 'Tweede Bakker', 'email' => 'tweede@example.test']);
        app(MarkOrderPaid::class)($entry->order);
        $owner = $entry->company->owners()->first();

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertOk()
            ->assertSee('Gaat in na de bekendmaking')
            ->assertSee('Plek 2 · € 872,00 excl. btw (regulier € 1.950,00)');

        $this->actingAs($owner, 'participant')->post(route('portaal.zichtbaarheid', $entry->company), ['position' => 2])->assertRedirect();

        $spot = $entry->visibilitySpot()->firstOrFail();
        $this->post(route('betaling.fake.afronden', $spot->order->latestPayment), ['status' => 'paid']);
        $spot->refresh();

        $this->assertTrue($spot->isPaid());
        $this->assertTrue($publication->equalTo($spot->starts_at));
        $this->assertFalse($spot->isShowing());

        // De plek die al verkocht was, blijft zoals hij was: direct zichtbaar.
        $this->assertNull($sold->visibilitySpot->fresh()->starts_at);
        $live = fn (): array => app(VisibilitySpots::class)->live($this->edition->fresh(), $entry->province)->pluck('position')->all();
        $this->assertSame([1], $live());

        $this->travelTo($publication->addMinute());

        $this->assertTrue($spot->fresh()->isShowing());
        $this->assertSame([1, 2], $live());
    }

    #[Test]
    public function a_baker_buys_a_spot_in_the_portal_and_gets_an_own_invoice(): void
    {
        Notification::fake();

        $entry = $this->register();
        app(MarkOrderPaid::class)($entry->order);
        $owner = $entry->company->owners()->first();

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertOk()
            ->assertSee('Extra zichtbaarheid')
            ->assertSee('Plek 10 · € 250,00 excl. btw');

        $this->actingAs($owner, 'participant')
            ->post(route('portaal.zichtbaarheid', $entry->company), ['position' => 10])
            ->assertRedirect();

        $spot = $entry->visibilitySpot()->firstOrFail();
        $order = $spot->order;
        $this->assertSame(30250, $order->total_cents); // EUR 250 + 21% btw

        $this->post(route('betaling.fake.afronden', $order->latestPayment), ['status' => 'paid'])->assertRedirect(route('portaal.zichtbaarheid.terug', $order));

        $this->actingAs($owner, 'participant')->get(route('portaal.zichtbaarheid.terug', $order))->assertRedirect(route('portaal.aanmelding', $entry->company));

        $this->assertTrue($spot->fresh()->isPaid());
        $this->assertSame('Bakkerij Testers', $order->fresh()->invoice->billing_name);
        $this->assertSame(25000, (int) CharityReservation::query()->whereIn('order_line_id', $order->lines()->pluck('id'))->sum('basis_cents'));

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertSee('Plek 10 in Gelderland')
            ->assertSee($order->fresh()->invoice->number)
            ->assertDontSee('Kiezen en afrekenen');
    }

    #[Test]
    public function the_portal_shows_the_opening_price_and_each_extra_with_its_own_amount(): void
    {
        $this->edition->forceFill(['settings' => $this->edition->settings->with(['opening_offer_cents' => 7500, 'plain_addon_cents' => 5000])])->save();

        $entry = $this->register(['pickup' => true, 'plainAddon' => true, 'rate' => EntryRate::OpeningOffer]);
        app(MarkOrderPaid::class)($entry->order);

        $this->actingAs($entry->company->owners()->first(), 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertOk()
            ->assertSee('€ 75,00 excl. btw')
            ->assertSee('€ 50,00 excl. btw')
            ->assertSee('€ 45,00 excl. btw')
            ->assertDontSee('€ 120,00');
    }

    #[Test]
    public function the_ranking_domain_knows_nothing_about_paid_spots(): void
    {
        foreach (File::allFiles(app_path('Domain/Ranking')) as $file) {
            $this->assertStringNotContainsString('VisibilitySpot', $file->getContents(), $file->getRelativePathname());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function register(array $overrides = []): Entry
    {
        return app(RegisterEntry::class)($this->edition->fresh(), $this->registrationData($overrides))->fresh(['order.lines', 'visibilitySpot']);
    }
}
