<?php

namespace Tests\Feature\Charities;

use App\Domain\Charities\Actions\NominateCharity;
use App\Domain\Charities\Actions\RecordPayout;
use App\Domain\Charities\Actions\ReviewCharity;
use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Models\ParticipantUser;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class CharityReservationTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function a_payment_reserves_ten_percent_that_follows_the_nomination_through_review_and_payout(): void
    {
        Notification::fake();

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'een@example.test', 'companyName' => 'Bakkerij Een', 'publicName' => 'Bakkerij Een', 'kvkNumber' => '11111111']));
        app(MarkOrderPaid::class)($entry->order);

        $line = $entry->order->fresh()->lines()->firstOrFail();
        $reservation = CharityReservation::query()->where('order_line_id', $line->getKey())->firstOrFail();
        $expected = (int) round($line->subtotal_cents * 0.10);

        $this->assertSame($expected, $reservation->amount_cents);
        $this->assertSame($line->subtotal_cents, $reservation->basis_cents);
        $this->assertNull($reservation->charity_id);
        $this->assertSame($entry->province_id, $reservation->regional_pot_province_id);
        $this->assertSame($entry->company->getMorphClass(), $reservation->payer_type);

        // Nogmaals betalen (webhook én redirect) reserveert niet dubbel.
        app(MarkOrderPaid::class)($entry->order->fresh());
        $this->assertSame(1, CharityReservation::query()->count());

        $this->get(route('goede-doelen'))->assertOk()->assertSee('Regionale potten')->assertSee('Gelderland')->assertSee(Money::format($expected));

        // Voordracht via het portaal, één per bedrijf.
        $owner = $entry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();
        $payload = ['name' => 'Stichting Test', 'motivation' => 'Helpt kinderen in de buurt.', 'province_id' => $entry->province_id, 'category' => 'jeugd', 'is_anbi' => '1', 'website' => 'https://stichting.example.test'];

        $this->actingAs($owner, 'participant')->post(route('portaal.goed-doel.voordragen', $entry->company), $payload)->assertRedirect(route('portaal.goed-doel', $entry->company));
        $this->actingAs($owner, 'participant')->post(route('portaal.goed-doel.voordragen', $entry->company), $payload)->assertSessionHasErrors('name');

        $charity = Charity::query()->where('name', 'Stichting Test')->firstOrFail();
        $this->assertSame(CharityStatus::Nominated, $charity->status);
        $this->assertTrue($charity->is_anbi);
        $this->assertNull($reservation->fresh()->charity_id);

        $this->actingAs($owner, 'participant')->get(route('portaal.goed-doel', $entry->company))->assertOk()->assertSee('Stichting Test')->assertSee('Voorgedragen');

        // Beoordeling: goedkeuren vereist de volledige checklist; daarna volgt de reservering het doel.
        $finance = User::factory()->create();
        $review = app(ReviewCharity::class);
        $review($charity, CharityStatus::Reviewing, null, null, $finance);

        try {
            $review($charity->fresh(), CharityStatus::Approved, ['legal'], null, $finance);
            $this->fail('Onvolledige checklist had moeten blokkeren.');
        } catch (CharityException) {
        }

        $review($charity->fresh(), CharityStatus::Approved, array_keys(config('charities.checklist')), 'Akkoord', $finance);
        $this->assertSame($charity->getKey(), $reservation->fresh()->charity_id);

        $this->get(route('goede-doelen'))->assertOk()->assertSee('Stichting Test')->assertSee('Goedgekeurd')->assertSee('ANBI')->assertDontSee('Regionale potten');

        // Alternatief in overleg → terug in de pot.
        $review($charity->fresh(), CharityStatus::Alternative, null, 'Geen ANBI-bewijs ontvangen', $finance);
        $this->assertNull($reservation->fresh()->charity_id);
        $this->actingAs($owner, 'participant')->get(route('portaal.goed-doel', $entry->company))->assertOk()->assertSee('Geen ANBI-bewijs ontvangen');

        // Alsnog goedkeuren, koppelen en uitbetalen.
        $review($charity->fresh(), CharityStatus::Approved, array_keys(config('charities.checklist')), null, $finance);
        $review($charity->fresh(), CharityStatus::Linked, null, null, $finance);
        app(RecordPayout::class)($charity->fresh(), $expected, now(), 'BANK-123', null, $finance);

        $charity->refresh();
        $this->assertSame(CharityStatus::PaidOut, $charity->status);
        $this->assertSame($expected, $charity->paidCents());
        $this->get(route('goede-doelen'))->assertOk()->assertSee('Uitbetaald')->assertSee('BANK-123' === '' ? '' : Money::format($expected));

        // Een sponsor kan ook voordragen, los van het bedrijf.
        $sponsor = Sponsor::query()->create(['name' => 'Sponsor Test']);
        $nominated = app(NominateCharity::class)($sponsor, $this->edition, ['name' => 'Fonds Sponsor', 'motivation' => 'Regionaal fonds.']);
        $this->assertSame($sponsor->getMorphClass(), $nominated->nominated_by_type);

        // Een medewerker mag niet voordragen.
        $staff = ParticipantUser::factory()->create();
        $entry->company->users()->attach($staff, ['role' => CompanyUserRole::Staff->value]);
        $this->actingAs($staff, 'participant')->post(route('portaal.goed-doel.voordragen', $entry->company), $payload)->assertForbidden();
    }
}
