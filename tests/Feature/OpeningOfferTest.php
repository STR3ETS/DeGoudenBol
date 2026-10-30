<?php

namespace Tests\Feature;

use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use App\Livewire\Public\RegistrationWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Openingsactie (besluit Bennie 30 okt 2026): deelname EUR 75 in plaats van EUR 150 en voor EUR 50 extra
 * ook de naturel oliebol laten testen. Vervangt zolang hij loopt de standaardprijs en de kortingsactie.
 */
class OpeningOfferTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
        $this->edition->forceFill(['settings' => $this->edition->settings->with(['opening_offer_cents' => 7500, 'plain_addon_cents' => 5000])])->save();
    }

    #[Test]
    public function the_opening_offer_replaces_the_discount_and_bills_the_naturel_test_as_an_extra_line(): void
    {
        Notification::fake();

        $entry = $this->register(['rate' => EntryRate::Discount, 'discountProofUrl' => 'https://www.instagram.com/p/oliebol', 'plainAddon' => true]);

        $this->assertSame(EntryRate::OpeningOffer, $entry->rate);
        $this->assertNull($entry->discount_status);
        $this->assertTrue($entry->plain_addon);
        $this->assertTrue($entry->canPay());

        $lines = $entry->order->lines;
        $this->assertSame([15000, -7500, 5000], $lines->pluck('subtotal_cents')->all());
        $this->assertSame(['opening_offer', 'plain_addon'], $lines->pluck('lineable_type')->slice(1)->values()->all());
        $this->assertSame(15125, $entry->order->total_cents); // EUR 125 + 21% btw

        app(MarkOrderPaid::class)($entry->order);

        $this->assertSame(15125, $entry->order->fresh()->invoice->total_cents);

        // 10% voor goede doelen over wat er werkelijk betaald is: EUR 125 excl. btw.
        $basis = CharityReservation::query()->whereIn('order_line_id', $lines->pluck('id'))->sum('basis_cents');
        $this->assertSame(12500, (int) $basis);
    }

    #[Test]
    public function without_the_naturel_test_the_baker_pays_the_opening_price(): void
    {
        $entry = $this->register();

        $this->assertFalse($entry->plain_addon);
        $this->assertSame(9075, $entry->order->total_cents); // EUR 75 + 21% btw
    }

    #[Test]
    public function switching_the_offer_off_brings_back_the_standard_price(): void
    {
        $this->edition->forceFill(['settings' => $this->edition->settings->with(['opening_offer_cents' => 0, 'plain_addon_cents' => 0])])->save();

        $entry = $this->register(['plainAddon' => true]);

        $this->assertSame(EntryRate::Standard, $entry->rate);
        $this->assertFalse($entry->plain_addon);
        $this->assertSame(18150, $entry->order->total_cents);
    }

    #[Test]
    public function the_wizard_shows_the_offer_and_totals_the_same_lines_as_the_order(): void
    {
        $wizard = Livewire::test(RegistrationWizard::class)
            ->set('packageId', $this->package->getKey())
            ->set('step', 4)
            ->assertSee('Openingsactie')
            ->assertSee('€ 75,00')
            ->assertSee('Test ook uw naturel oliebol')
            ->assertDontSee('Actieprijs')
            ->set('plainAddon', true);

        $this->assertSame(['subtotal_cents' => 12500, 'total_cents' => 15125], $wizard->instance()->orderTotals());
        $this->assertSame(
            CreateOrderForEntry::totals($this->package, $this->edition->fresh()->settings, 2026, EntryRate::OpeningOffer, true),
            $wizard->instance()->orderTotals(),
        );

        $this->get(route('deelnemen'))->assertOk()->assertSee('Openingsactie:', false)->assertSee('ook je naturel oliebol');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function register(array $overrides = []): Entry
    {
        return app(RegisterEntry::class)($this->edition, $this->registrationData($overrides))->fresh(['order.lines']);
    }
}
