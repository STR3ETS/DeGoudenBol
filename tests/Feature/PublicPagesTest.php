<?php

namespace Tests\Feature;

use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_homepage_renders_without_an_edition(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Wie bakt de')
            ->assertSee('De Gouden Bol')
            ->assertSee('id="gb-live"', false);
    }

    #[Test]
    public function the_homepage_shows_the_provinces_with_live_capacity_on_the_map(): void
    {
        $this->travelTo('2026-10-01 10:00:00');

        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Drenthe')
            ->assertSee('Zuid-Holland')
            ->assertSee('Max. 50 deelnemers', false)
            ->assertSee('Voorlijst opent 1 november', false)
            ->assertSee('Smaak')
            ->assertSee('Veelgestelde vragen')
            ->assertSee('Editie 2026 in vier momenten')
            ->assertSee('"capacity":600', false);
    }

    #[Test]
    public function the_site_serves_its_own_assets(): void
    {
        $this->assertFileExists(public_path('site/assets/82843df790355b2d6db762b59a8ceb2db51b6cf1.css'));
        $this->assertFileExists(public_path('site/assets/f09daa7f7315e68a8b4af036abab52ea88446eb7.js'));
        $this->assertFileExists(public_path('site/images/5097bdefb5478dc6e6537a40211dbf6432a82738.png'));
        $this->assertStringNotContainsString('storage/cms-assets', file_get_contents(public_path('site/assets/82843df790355b2d6db762b59a8ceb2db51b6cf1.css')));
    }

    #[Test]
    public function navigation_only_links_to_existing_routes(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (['provincies.index', 'bakkers.index', 'deelnemen', 'finale', 'goede-doelen', 'sponsoren', 'pers', 'faq', 'over', 'privacy', 'voorwaarden'] as $name) {
            $response->assertSee('href="'.route($name, [], false).'"', false);
        }
    }

    #[Test]
    public function the_styleguide_is_only_available_when_enabled(): void
    {
        $this->seed(ProvinceSeeder::class);

        config()->set('site.styleguide_enabled', true);
        $this->get('/stijlgids')->assertOk()->assertSee('Het Register')->assertSee('Zes principes');
        $this->get('/redesign-dashboard')->assertOk()->assertSee('Jouw werk vandaag');
        $this->get('/redesign-dashboard-klant')->assertOk()->assertSee('Jouw volgende stap');

        config()->set('site.styleguide_enabled', false);
        $this->get('/stijlgids')->assertNotFound();
        $this->get('/redesign-dashboard')->assertNotFound();
        $this->get('/redesign-dashboard-klant')->assertNotFound();
    }
}
