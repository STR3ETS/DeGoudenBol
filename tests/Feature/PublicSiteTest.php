<?php

namespace Tests\Feature;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * De publiekssite is de site-export van Eazyonline (tools/site-import.mjs) met de gegevens uit de app erin.
 */
class PublicSiteTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    private function confirmedEntry(array $overrides = []): Entry
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData($overrides));
        app(MarkOrderPaid::class)($entry->order);

        return $entry->refresh();
    }

    #[Test]
    public function the_province_pages_show_the_capacity_and_the_empty_ranking(): void
    {
        $this->confirmedEntry();
        app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Onbetaald', 'publicName' => 'Onbetaalde Bakker']));

        $this->get(route('provincies.index'))->assertOk()->assertSee('Gelderland')->assertSee('Kies je provincie');

        $this->get(route('provincies.show', Province::query()->where('slug', 'gelderland')->first()))
            ->assertOk()
            ->assertSee('De beste oliebollen van')
            ->assertSee('2 van de 50 plaatsen ingevuld; nog 48 beschikbaar') // de reservering telt mee tot hij vervalt
            ->assertSee('Nog niet bekend')
            ->assertSee('Er zijn nog geen scores gepubliceerd')
            ->assertSee('2 van 50 plekken bezet', false);
    }

    #[Test]
    public function the_participants_overview_and_profiles_only_show_confirmed_entries(): void
    {
        $entry = $this->confirmedEntry();
        $this->confirmedEntry(['email' => 'c@example.test', 'companyName' => 'Tweede Bakker', 'publicName' => 'Tweede Bakker']);
        $pending = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Onbetaald', 'publicName' => 'Onbetaalde Bakker']));

        $this->get(route('bakkers.index'))
            ->assertOk()
            ->assertSee('Bakkerij Testers')
            ->assertSee('Tweede Bakker')
            ->assertDontSee('Onbetaalde Bakker')
            ->assertDontSee('fictief voorbeeld')
            ->assertSee('data-prov="Gelderland"', false)
            ->assertSee('2 bakkers doen mee');

        $this->get(route('bakkers.toon', $entry->company))
            ->assertOk()
            ->assertSee('Bakkerij Testers')
            ->assertSee('Deelnemer De Gouden Bol 2026')
            ->assertSee('nog niet beoordeeld')
            ->assertSee('application/ld+json', false);

        $this->get(route('bakkers.toon', $pending->company))->assertNotFound();
        $this->get('/bakker/'.$entry->company->slug)->assertRedirect(route('bakkers.toon', $entry->company));
        $this->get('/bakkers')->assertRedirect('/deelnemers');
    }

    #[Test]
    public function the_live_json_feeds_the_widget_with_signups_and_rankings(): void
    {
        $entry = $this->confirmedEntry();

        $json = $this->get('/live.json')->assertOk()->json();

        $this->assertSame('2026', $json['edition']);
        $this->assertSame(600, $json['capacity']);
        $this->assertSame(1, $json['total']);
        $this->assertSame('Bakkerij Testers', $json['signups'][0]['name']);
        $this->assertSame('Gelderland', $json['signups'][0]['province']);
        $this->assertSame(route('bakkers.toon', $entry->company), $json['signups'][0]['url']);
        $this->assertArrayHasKey('gelderland', $json['rankings']);
        $this->assertCount(12, $json['provinces']);
    }

    #[Test]
    public function the_static_pages_and_old_addresses_keep_working(): void
    {
        $this->get(route('hoe-werkt-de-test'))->assertOk()->assertSee('De reis van');
        $this->get('/hoe-werkt-de-test')->assertRedirect('/test');
        $this->get('/het-panel')->assertRedirect('/test#organisatie');
        $this->get(route('faq'))->assertOk()->assertSee('Alles wat je wilt weten');
        $this->get(route('voorwaarden'))->assertOk()->assertSee('Voorwaarden en protocollen');
        $this->get(route('voorwaarden.deelname'))->assertOk()->assertSee('Algemene voorwaarden voor deelneming');
        $this->get(route('actievoorwaarden'))->assertOk()->assertSee('Actievoorwaarden cadeaubonnen');
        $this->get('/actievoorwaarden')->assertRedirect('/voorwaarden/cadeaubonnen');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacyverklaring');
        $this->get(route('sponsoren'))->assertOk()->assertSee('Sponsor');
        $this->get('/sponsoren')->assertRedirect('/sponsoring');
        $this->get(route('finale'))->assertOk()->assertSee('finale');
        $this->get('/bestaat-niet')->assertNotFound()->assertSee('Deze pagina');
    }

    #[Test]
    public function the_join_page_sends_bakers_to_the_registration_wizard_when_open(): void
    {
        $this->get(route('deelnemen'))->assertOk()->assertSee('Start je aanmelding')->assertSee(route('aanmelden'))->assertDontSee('Aanmeldformulier');

        // Status Testfase volgt de datums; een openingsdatum in de toekomst sluit de aanmelding.
        $this->edition->forceFill(['status' => EditionStatus::Testing, 'registration_opens_at' => now()->addMonth()])->save();

        $this->get(route('deelnemen'))->assertOk()->assertSee('De inschrijving opent op')->assertDontSee('Start je aanmelding');
    }

    #[Test]
    public function news_lives_on_the_press_page_and_only_shows_published_posts(): void
    {
        $published = NewsPost::factory()->create(['title' => 'Inschrijving geopend']);
        $draft = NewsPost::factory()->draft()->create(['title' => 'Nog geheim']);

        $this->get('/nieuws')->assertRedirect('/pers#nieuws');
        $this->get(route('pers'))->assertOk()->assertSee('Inschrijving geopend')->assertDontSee('Nog geheim');
        $this->get(route('nieuws.toon', $published))->assertOk()->assertSee('Inschrijving geopend');
        $this->get(route('nieuws.toon', $draft))->assertNotFound();
    }

    #[Test]
    public function the_sitemap_lists_provinces_confirmed_bakers_and_news(): void
    {
        $entry = $this->confirmedEntry();
        NewsPost::factory()->create(['title' => 'Bericht']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('provincies.show', 'gelderland'))
            ->assertSee(route('bakkers.toon', $entry->company))
            ->assertSee(route('deelnemen'))
            ->assertSee('/nieuws/bericht');
    }

    #[Test]
    public function the_navigation_links_to_the_real_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('provincies.index', [], false).'"', false)
            ->assertSee('href="'.route('bakkers.index', [], false).'"', false)
            ->assertSee('href="'.route('hoe-werkt-de-test', [], false).'"', false)
            ->assertSee('href="'.route('deelnemen', [], false).'"', false)
            ->assertSee('href="'.route('pers', [], false).'"', false)
            ->assertSee('id="gb-live"', false)
            ->assertDontSee('storage/cms-assets')
            ->assertDontSee('href="../', false);
    }
}
