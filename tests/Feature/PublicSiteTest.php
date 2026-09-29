<?php

namespace Tests\Feature;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

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
    public function the_province_pages_list_confirmed_participants_and_capacity(): void
    {
        $entry = $this->confirmedEntry();
        app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Onbetaald', 'publicName' => 'Onbetaalde Bakker']));

        $this->get(route('provincies.index'))
            ->assertOk()
            ->assertSee('Gelderland')
            ->assertSee('2 aangemeld · 48 van 50 plekken vrij');

        $this->get(route('provincies.show', Province::query()->where('slug', 'gelderland')->first()))
            ->assertOk()
            ->assertSee('De beste oliebollen')
            ->assertSee('1 deelnemer in Gelderland')
            ->assertSee($entry->public_name)
            ->assertDontSee('Onbetaalde Bakker');
    }

    #[Test]
    public function the_bakkers_overview_filters_and_the_profile_page_only_exists_for_confirmed_entries(): void
    {
        $entry = $this->confirmedEntry();
        $second = $this->confirmedEntry(['email' => 'c@example.test', 'companyName' => 'Tweede Bakker', 'publicName' => 'Tweede Bakker']);
        $pending = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Onbetaald', 'publicName' => 'Onbetaalde Bakker']));

        // Meerdere deelnemers: hier viel de lazy-loading-bewaking eerder over.
        $this->get(route('bakkers.index'))->assertOk()->assertSee('Bakkerij Testers')->assertSee('Tweede Bakker')->assertDontSee('Onbetaalde Bakker');
        $this->get(route('provincies.show', 'gelderland'))->assertOk()->assertSee('2 deelnemers in Gelderland');
        $this->get(route('bakkers.index', ['provincie' => 'zeeland']))->assertOk()->assertSee('Geen bakkers gevonden');
        $this->get(route('bakkers.index', ['q' => 'Arnhem']))->assertOk()->assertSee('Bakkerij Testers');

        $this->get(route('bakkers.toon', $entry->company))
            ->assertOk()
            ->assertSee('Bakkerij Testers')
            ->assertSee('Deelnemer 2026')
            ->assertSee('Nog niet beoordeeld')
            ->assertSee('application/ld+json', false);

        $this->get(route('bakkers.toon', $pending->company))->assertNotFound();
    }

    #[Test]
    public function the_explanation_pages_use_the_edition_settings(): void
    {
        $this->get(route('hoe-werkt-de-test'))
            ->assertOk()
            ->assertSee('Smaak')
            ->assertSee('minimaal 6 kaarten')
            ->assertSee('maandag 21 december 2026');

        $this->get(route('panel'))->assertOk()->assertSee('Onafhankelijk en');
    }

    #[Test]
    public function legal_pages_show_the_published_version_or_a_notice(): void
    {
        $this->get(route('voorwaarden'))->assertOk()->assertSee('Versie 2026.1');

        $this->get(route('privacy'))->assertOk()->assertSee('Nog niet gepubliceerd');

        TermsVersion::factory()->ofType(TermsType::Privacy)->published()->create(['version' => '2026.9', 'body' => '<p>Privacytekst van de jurist.</p>']);

        $this->get(route('privacy'))->assertOk()->assertSee('Privacytekst van de jurist.');
    }

    #[Test]
    public function news_only_shows_published_posts(): void
    {
        $published = NewsPost::factory()->create(['title' => 'Inschrijving geopend']);
        $draft = NewsPost::factory()->draft()->create(['title' => 'Nog geheim']);

        $this->get(route('nieuws.index'))->assertOk()->assertSee('Inschrijving geopend')->assertDontSee('Nog geheim');
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
            ->assertSee('/nieuws/bericht');
    }

    #[Test]
    public function the_navigation_now_links_to_the_real_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('provincies.index'))
            ->assertSee(route('bakkers.index'))
            ->assertSee(route('hoe-werkt-de-test'))
            ->assertSee(route('nieuws.index'));
    }
}
