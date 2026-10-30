<?php

namespace Tests\Feature;

use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Google Analytics laadt pas na toestemming. De keuze staat in de browser van de bezoeker, dus de
 * pagina's zelf blijven hetzelfde en de paginacache blijft werken.
 */
class CookieKeuzeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);
        config(['site.gtm_id' => null]);
    }

    #[Test]
    public function de_balk_staat_op_de_publiekssite_en_analytics_laadt_niet_vanzelf(): void
    {
        $this->app['env'] = 'production';

        $this->get('/')->assertOk()
            ->assertSee('G-6V0TEJWL2E', false)
            ->assertSee('Statistieken toestaan')
            ->assertSee('data-cookie-instellingen', false)
            ->assertDontSee('<script async src="https://www.googletagmanager.com', false);
    }

    #[Test]
    public function de_oude_layout_heeft_de_balk_ook(): void
    {
        $this->withoutVite();
        $this->app['env'] = 'production';

        $this->get(route('aanmelden'))->assertOk()->assertSee('dgb-keuze-v1', false)->assertSee('data-cookie-instellingen', false);
    }

    #[Test]
    public function buiten_productie_meten_we_niets(): void
    {
        $this->get('/')->assertOk()->assertDontSee('G-6V0TEJWL2E', false);
    }

    #[Test]
    public function het_portaal_blijft_erbuiten(): void
    {
        $this->withoutVite();
        $this->app['env'] = 'production';

        $this->get(route('portaal.inloggen'))->assertOk()->assertDontSee('G-6V0TEJWL2E', false);
    }

    #[Test]
    public function de_privacyverklaring_noemt_analytics_en_de_keuze(): void
    {
        $this->get('/privacy')->assertOk()
            ->assertSee('Google Analytics')
            ->assertSee('Cookie-instellingen')
            ->assertDontSee('geen tracking- of advertentiecookies');
    }
}
