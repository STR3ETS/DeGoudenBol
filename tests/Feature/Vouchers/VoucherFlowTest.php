<?php

namespace Tests\Feature\Vouchers;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Marketing\Enums\Milestone;
use App\Domain\Marketing\Services\MilestoneResolver;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Ranking\Enums\SnapshotStatus;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Vouchers\Actions\AddWinner;
use App\Domain\Vouchers\Actions\AnonymizeWinners;
use App\Domain\Vouchers\Actions\ClaimVoucher;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Domain\Vouchers\Notifications\CampaignOpenedNotification;
use App\Domain\Vouchers\Notifications\VoucherClaimNotification;
use App\Domain\Vouchers\Notifications\VoucherIssuedNotification;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Support\DutchTime;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class VoucherFlowTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $first;

    private Entry $second;

    private TermsVersion $campaignTerms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->first = $this->registered('Bakkerij Een', 'een@example.test', '11111111');
        $this->second = $this->registered('Bakkerij Twee', 'twee@example.test', '22222222');

        $this->edition->forceFill([
            'main_publication_at' => now()->subHour(),
            'last_redeem_day' => now()->addDays(7)->toDateString(),
        ])->save();

        $this->campaignTerms = TermsVersion::factory()->ofType(TermsType::VoucherCampaign)->published()->create([
            'edition_id' => $this->edition->getKey(),
            'version' => '2026.1',
        ]);

        $snapshot = RankingSnapshot::query()->create([
            'edition_id' => $this->edition->getKey(),
            'province_id' => $this->first->province_id,
            'scope' => 'province:'.$this->first->province_id,
            'round' => 'provincial',
            'engine_version' => '2026.1',
            'status' => SnapshotStatus::Frozen,
            'input_hash' => 'test',
            'computed_at' => now(),
            'published_at' => now(),
        ]);

        $snapshot->positions()->create(['entry_id' => $this->first->getKey(), 'position' => 1, 'total' => 8.4, 'label' => 'new', 'needs_tie_break' => false]);
        $snapshot->positions()->create(['entry_id' => $this->second->getKey(), 'position' => 2, 'total' => 8.1, 'label' => 'new', 'needs_tie_break' => false]);
    }

    #[Test]
    public function campaigns_are_created_on_freeze_and_opened_by_the_tick(): void
    {
        Notification::fake();

        EditionFrozen::dispatch($this->edition->fresh());

        $this->assertSame(2, VoucherCampaign::query()->count());

        $campaign = VoucherCampaign::query()->where('entry_id', $this->first->getKey())->firstOrFail();
        $this->assertSame(CampaignStatus::Draft, $campaign->status);
        $this->assertSame(10, $campaign->winner_count);
        $this->assertSame(4500, $campaign->voucher_value_cents);
        $this->assertSame($this->campaignTerms->getKey(), $campaign->terms_version_id);
        $this->assertSame('12:00', DutchTime::display($campaign->winners_deadline_at)->format('H:i'));
        $this->assertTrue($campaign->winners_deadline_at->greaterThan($campaign->starts_at->addDay()));

        // Nog niet open: mijlpaal wel zichtbaar maar onder embargo tot de start.
        $reached = app(MilestoneResolver::class)->forEntry($this->first->fresh());
        $this->assertTrue($reached->contains(fn (array $item) => $item['milestone'] === Milestone::VoucherStart && $item['available_from'] !== null));

        // Dezelfde bevriezing nog eens → geen dubbele acties.
        EditionFrozen::dispatch($this->edition->fresh());
        $this->assertSame(2, VoucherCampaign::query()->count());

        $this->artisan('vouchers:tick')->assertSuccessful();

        $this->assertSame(CampaignStatus::Open, $campaign->fresh()->status);
        Notification::assertSentTo($this->ownerOf($this->first), CampaignOpenedNotification::class);
        $this->assertTrue(app(MilestoneResolver::class)->isReached($this->first->fresh(), Milestone::VoucherStart));
    }

    #[Test]
    public function a_winner_is_added_claims_the_voucher_and_it_can_be_redeemed_only_once_by_the_issuer(): void
    {
        Notification::fake();
        $this->openCampaigns();

        $campaign = VoucherCampaign::query()->where('entry_id', $this->first->getKey())->firstOrFail();
        $owner = $this->ownerOf($this->first);
        $otherOwner = $this->ownerOf($this->second);

        // Stap 1: de ondernemer voert een winnaar in.
        $this->actingAs($owner, 'participant')
            ->post(route('portaal.cadeaubonnen.winnaar', $this->first->company), ['first_name' => 'Anna', 'last_initial' => 'j', 'email' => 'Anna@Example.test'])
            ->assertRedirect(route('portaal.cadeaubonnen', $this->first->company))
            ->assertSessionHas('status');

        $winner = VoucherWinner::query()->where('voucher_campaign_id', $campaign->getKey())->firstOrFail();
        $this->assertSame('anna@example.test', $winner->email);
        $this->assertSame('Anna J.', $winner->displayName());

        $claimToken = null;
        Notification::assertSentOnDemand(VoucherClaimNotification::class, function (VoucherClaimNotification $notification) use (&$claimToken): bool {
            $claimToken = $notification->token;

            return true;
        });
        $this->assertNotNull($claimToken);

        // Maximaal één bon per e-mailadres per editie, ook bij een andere ondernemer.
        $this->actingAs($otherOwner, 'participant')
            ->post(route('portaal.cadeaubonnen.winnaar', $this->second->company), ['first_name' => 'Anna', 'last_initial' => 'J', 'email' => 'anna@example.test'])
            ->assertSessionHasErrors('email');

        // Een medewerker met scannerrol mag geen winnaars invoeren.
        $scanner = ParticipantUser::factory()->create();
        $this->second->company->users()->attach($scanner, ['role' => CompanyUserRole::Scanner->value]);
        $this->actingAs($scanner, 'participant')
            ->post(route('portaal.cadeaubonnen.winnaar', $this->second->company), ['first_name' => 'Bo', 'last_initial' => 'K', 'email' => 'bo@example.test'])
            ->assertForbidden();

        // Stap 2 en 3: de winnaar claimt, accepteert de voorwaarden en geeft toestemming voor de naam.
        $this->get(route('cadeaubon.claim', $claimToken))->assertOk()->assertSee('Anna')->assertSee('Bakkerij Een');

        $this->post(route('cadeaubon.claim.bevestigen', $claimToken), [])->assertSessionHasErrors('terms');

        $response = $this->post(route('cadeaubon.claim.bevestigen', $claimToken), ['terms' => '1', 'terms_version_id' => $this->campaignTerms->getKey(), 'consent_public_name' => '1']);

        $voucher = Voucher::query()->where('voucher_winner_id', $winner->getKey())->firstOrFail();
        $this->assertSame(VoucherStatus::Issued, $voucher->status);
        $this->assertMatchesRegularExpression('/^GB26-GLD-[A-HJ-NP-Z2-9]{4}$/', $voucher->code);
        $this->assertSame(4500, $voucher->value_cents);
        $this->assertSame($this->campaignTerms->getKey(), $winner->fresh()->terms_version_id);
        $this->assertNotNull($winner->fresh()->claimed_at);

        $voucherToken = null;
        Notification::assertSentOnDemand(VoucherIssuedNotification::class, function (VoucherIssuedNotification $notification) use (&$voucherToken): bool {
            $voucherToken = $notification->token;

            return true;
        });
        $response->assertRedirect(route('bon.toon', $voucherToken));

        // Nogmaals claimen kan niet.
        $this->post(route('cadeaubon.claim.bevestigen', $claimToken), ['terms' => '1'])->assertSessionHasErrors('terms');
        $this->assertSame(1, Voucher::query()->count());

        // De bonpagina toont status en nummer, geen persoonsgegevens.
        $this->get(route('bon.toon', $voucherToken))->assertOk()->assertSee('Geldig')->assertSee($voucher->code)->assertDontSee('anna@example.test');
        $this->get(route('bon.toon', 'onbekend-token-0000000000'))->assertNotFound();

        // Winnaarspagina: alleen met toestemming, als "Voornaam L.".
        $this->get(route('winnaars'))->assertOk()->assertSee('Anna J.')->assertDontSee('anna@example.test');

        // Stap 4: verzilveren kan alleen bij de uitgevende ondernemer.
        $this->actingAs($otherOwner, 'participant')
            ->post(route('portaal.scan.verzilveren', $this->second->company), ['code' => $voucher->code, 'method' => 'manual'])
            ->assertRedirect(route('portaal.scan', $this->second->company))
            ->assertSessionHas('outcome', fn (array $outcome) => $outcome['result'] === 'wrong_company');
        $this->assertSame(VoucherStatus::Issued, $voucher->fresh()->status);

        $this->actingAs($owner, 'participant')
            ->post(route('portaal.scan.verzilveren', $this->first->company), ['code' => route('bon.toon', $voucherToken), 'method' => 'scan'])
            ->assertSessionHas('outcome', fn (array $outcome) => $outcome['result'] === 'redeemed' && $outcome['title'] === 'Verzilverd' && $outcome['photo_allowed'] === false);

        $voucher->refresh();
        $this->assertSame(VoucherStatus::Redeemed, $voucher->status);
        $this->assertNotNull($voucher->redeemed_at);
        $this->assertSame(1, $voucher->redemptions()->where('result', 'redeemed')->count());

        // Tweede keer: al gebruikt, ook via het leesbare nummer met spaties en kleine letters.
        $this->actingAs($owner, 'participant')
            ->post(route('portaal.scan.verzilveren', $this->first->company), ['code' => strtolower(str_replace('-', ' ', $voucher->code))])
            ->assertSessionHas('outcome', fn (array $outcome) => $outcome['result'] === 'already_redeemed');

        $this->get(route('bon.toon', $voucherToken))->assertOk()->assertSee('Verzilverd');

        // Onbekende code → weigering met reden.
        $this->actingAs($owner, 'participant')
            ->post(route('portaal.scan.verzilveren', $this->first->company), ['code' => 'GB26-GLD-ZZZZ'])
            ->assertSessionHas('outcome', fn (array $outcome) => $outcome['result'] === 'unknown');

        $this->actingAs($owner, 'participant')->get(route('portaal.cadeaubonnen', $this->first->company))->assertOk()->assertSee('Anna J.')->assertSee($voucher->code);
        $this->actingAs($owner, 'participant')->get(route('portaal.scan', $this->first->company))->assertOk()->assertSee('Scan de QR-code');
    }

    #[Test]
    public function the_tick_closes_with_a_shortfall_expires_vouchers_and_winners_are_anonymised_later(): void
    {
        Notification::fake();
        $this->openCampaigns();

        $campaign = VoucherCampaign::query()->where('entry_id', $this->first->getKey())->firstOrFail();

        $winner = app(AddWinner::class)($campaign, 'Bram', 'V', 'bram@example.test');
        $issued = app(ClaimVoucher::class)($winner, $this->campaignTerms->getKey(), false, true);

        $this->assertSame($issued['voucher']->qr_token_hash, app(VoucherCode::class)->hash($issued['token']));

        $this->travelTo($campaign->winners_deadline_at->addMinute());
        $this->artisan('vouchers:tick')->assertSuccessful();

        $campaign->refresh();
        $this->assertSame(CampaignStatus::Closed, $campaign->status);
        $this->assertSame(9, $campaign->report()['shortfall']);
        $this->assertFalse($campaign->acceptsWinners());

        // Bonbeheer kan wel aanvullen, de ondernemer niet meer.
        app(AddWinner::class)($campaign, 'Cas', 'D', 'cas@example.test', null, asVoucherManager: true);
        $this->assertSame(2, $campaign->winners()->count());

        $this->travelTo(DutchTime::display($campaign->last_redeem_day)->addDay()->setTime(3, 0));
        $this->artisan('vouchers:tick')->assertSuccessful();

        $this->assertSame(VoucherStatus::Expired, $issued['voucher']->fresh()->status);
        $this->assertSame(CampaignStatus::Expired, $campaign->fresh()->status);

        // Anonimiseren pas na de bewaardatum.
        config()->set('vouchers.anonymise_from', now()->addYear()->toDateString());
        $this->assertSame(0, app(AnonymizeWinners::class)());

        config()->set('vouchers.anonymise_from', now()->subDay()->toDateString());
        $this->assertSame(2, app(AnonymizeWinners::class)());
        $this->assertSame('Winnaar', $winner->fresh()->first_name);
        $this->assertStringStartsWith('geanonimiseerd-', $winner->fresh()->email);
    }

    private function openCampaigns(): void
    {
        EditionFrozen::dispatch($this->edition->fresh());
        $this->artisan('vouchers:tick')->assertSuccessful();
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
