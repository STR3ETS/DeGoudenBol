<?php

namespace Tests\Feature;

use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Google Tag Manager staat op de publiekssite in productie, niet lokaal en niet in het portaal.
 */
class TagManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);
        config(['site.gtm_id' => 'GTM-TV5Z4R84']);
    }

    #[Test]
    public function the_container_loads_on_the_public_site_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/')->assertOk()->assertSee('googletagmanager.com/gtm.js', false)->assertSee('GTM-TV5Z4R84', false)->assertSee('ns.html?id=GTM-TV5Z4R84', false);
        $this->get(route('aanmelden'))->assertOk()->assertSee('GTM-TV5Z4R84', false);
        $this->get(route('portaal.inloggen'))->assertOk()->assertDontSee('googletagmanager', false);
    }

    #[Test]
    public function it_stays_off_outside_production(): void
    {
        $this->get('/')->assertOk()->assertDontSee('googletagmanager', false);
    }
}
