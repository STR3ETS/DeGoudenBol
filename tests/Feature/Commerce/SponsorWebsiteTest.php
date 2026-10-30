<?php

namespace Tests\Feature\Commerce;

use App\Domain\Commerce\Actions\ApproveSponsor;
use App\Domain\Commerce\Actions\ReviewSponsorOffer;
use App\Domain\Commerce\Enums\OfferStatus;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorOffer;
use App\Domain\Commerce\Notifications\SponsorLinkRequestedNotification;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Sponsorpagina, aanmelding met Stripe-checkout, maatwerkaanvraag en sponsoromgeving (briefing 29 okt §8–§12).
 */
class SponsorWebsiteTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();
        $this->seed(ProductSeeder::class);

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();
    }

    #[Test]
    public function the_sponsor_page_shows_the_packages_with_prices_and_an_order_route(): void
    {
        $this->get(route('sponsoren'))
            ->assertOk()
            ->assertSee('Basis sponsor')
            ->assertSee('€ 250,00')
            ->assertSee('Deelnemerspartner')
            ->assertSee('Top 10 partner')
            ->assertSee('Hoofdsponsor')
            ->assertSee(route('sponsoring.aanmelden'), false);

        $this->get(route('sponsoring.aanmelden', ['pakket' => 'homepage']))->assertOk()->assertSee('Sponsor worden')->assertSee('Factuuradres');
    }

    #[Test]
    public function a_basic_sponsor_pays_by_checkout_and_becomes_visible_only_after_approval(): void
    {
        Notification::fake();

        $this->post(route('sponsoring.aanmelden.verwerken'), $this->form(ProductCode::Homepage))->assertRedirect();

        $sponsor = Sponsor::query()->where('name', 'Meelfabriek Test')->firstOrFail();
        $order = $sponsor->orders()->firstOrFail();
        $this->assertSame(SponsorStatus::Pending, $sponsor->status);
        $this->assertSame(30250, $order->total_cents); // EUR 250 + 21% btw

        $this->post(route('betaling.fake.afronden', $order->latestPayment), ['status' => 'paid'])->assertRedirect(route('sponsoring.status', $order));
        $this->get(route('sponsoring.status', $order))->assertOk()->assertSee('Bedankt voor uw');

        $this->assertNotNull($order->fresh()->invoice);
        Notification::assertSentOnDemand(SponsorMail::class, fn (SponsorMail $mail, array $channels, AnonymousNotifiable $to) => $mail->kind === SponsorMail::PAID && $to->routes['mail'] === 'sponsor@example.test');
        $this->get('/')->assertOk()->assertDontSee('Meelfabriek Test');

        app(ApproveSponsor::class)($sponsor->fresh(), User::factory()->create());

        $this->get('/')->assertOk()->assertSee('Meelfabriek Test');
        Notification::assertSentOnDemand(SponsorMail::class, fn (SponsorMail $mail) => $mail->kind === SponsorMail::PUBLISHED);
    }

    #[Test]
    public function a_participant_partner_asks_the_participant_only_after_payment(): void
    {
        Notification::fake();
        $owner = $this->entry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();

        $this->post(route('sponsoring.aanmelden.verwerken'), [...$this->form(ProductCode::ParticipantLink), 'entries' => [$this->entry->getKey()]])->assertRedirect();

        $order = Sponsor::query()->where('name', 'Meelfabriek Test')->firstOrFail()->orders()->firstOrFail();
        $this->assertSame(6050, $order->total_cents);
        Notification::assertNotSentTo($owner, SponsorLinkRequestedNotification::class);

        $this->post(route('betaling.fake.afronden', $order->latestPayment), ['status' => 'paid']);

        Notification::assertSentToTimes($owner, SponsorLinkRequestedNotification::class, 1);
    }

    #[Test]
    public function custom_packages_and_the_top_10_partner_are_requests_without_payment(): void
    {
        Notification::fake();

        $response = $this->post(route('sponsoring.aanmelden.verwerken'), [...$this->form(ProductCode::MainSponsor), 'note' => 'Graag landelijke zichtbaarheid rond de finale.']);

        $sponsor = Sponsor::query()->where('name', 'Meelfabriek Test')->firstOrFail();
        $response->assertRedirect(route('sponsoring.bedankt', $sponsor));
        $this->assertSame(SponsorStatus::Request, $sponsor->status);
        $this->assertSame(0, $sponsor->orders()->count());
        Notification::assertSentOnDemand(SponsorMail::class, fn (SponsorMail $mail) => $mail->kind === SponsorMail::REQUEST);

        $this->get(route('sponsoring.bedankt', $sponsor))->assertOk()->assertSee('Wij nemen contact met u op');
    }

    #[Test]
    public function the_sponsor_environment_opens_with_the_personal_link_and_offers_need_approval(): void
    {
        Notification::fake();

        $this->get(route('sponsor.dashboard'))->assertRedirect(route('sponsoring.beheer'));

        $sponsor = Sponsor::query()->create(['name' => 'Bakkersgilde', 'contact_email' => 'gilde@example.test', 'contact_name' => 'Gerda', 'status' => SponsorStatus::Active]);

        $this->get($sponsor->accessUrl())->assertRedirect(route('sponsor.dashboard'));
        $this->get(route('sponsor.dashboard'))->assertOk()->assertSee('Bakkersgilde')->assertSee('Sponsoractie aanbieden');

        $this->post(route('sponsor.actie'), ['type' => 'sample_pack', 'audience' => 'all', 'title' => 'Gratis proefpakket bloem', 'description' => 'Voor elke deelnemer een proefpakket.'])->assertRedirect(route('sponsor.dashboard'));

        $offer = SponsorOffer::query()->firstOrFail();
        $this->assertSame(OfferStatus::Submitted, $offer->status);

        $owner = $this->entry->company->users()->firstOrFail();
        $this->actingAs($owner, 'participant')->get(route('portaal.sponsoren', $this->entry->company))->assertOk()->assertDontSee('Gratis proefpakket bloem');

        $editor = User::factory()->create();
        $editor->assignRole(StaffRole::Communication->value);
        app(ReviewSponsorOffer::class)($offer, true, $editor);

        $this->actingAs($owner, 'participant')->get(route('portaal.sponsoren', $this->entry->company))->assertOk()->assertSee('Gratis proefpakket bloem');
        Notification::assertSentOnDemand(SponsorMail::class, fn (SponsorMail $mail) => $mail->kind === SponsorMail::OFFER_REVIEWED);
    }

    #[Test]
    public function asking_for_a_new_link_never_reveals_whether_an_address_is_known(): void
    {
        Notification::fake();
        Sponsor::query()->create(['name' => 'Bakkersgilde', 'contact_email' => 'gilde@example.test', 'status' => SponsorStatus::Active]);

        $known = $this->post(route('sponsoring.beheer.versturen'), ['email' => 'gilde@example.test']);
        $unknown = $this->post(route('sponsoring.beheer.versturen'), ['email' => 'onbekend@example.test']);

        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentOnDemandTimes(SponsorMail::class, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function form(ProductCode $code): array
    {
        return [
            'product' => Product::query()->where('edition_id', $this->edition->getKey())->where('code', $code)->value('id'),
            'company' => 'Meelfabriek Test',
            'kvk' => '87654321',
            'contact_name' => 'Sam Sponsor',
            'email' => 'sponsor@example.test',
            'phone' => '0201234567',
            'street' => 'Molenweg 3',
            'postcode' => '1234 AB',
            'city' => 'Zaandam',
            'website' => 'https://meelfabriek.example.test',
            'akkoord' => '1',
        ];
    }
}
