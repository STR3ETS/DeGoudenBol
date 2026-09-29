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
            ->assertSee('Nog geen gepubliceerde resultaten');
    }

    #[Test]
    public function the_homepage_shows_the_seeded_edition_facts_in_every_block(): void
    {
        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Inschrijving opent 12 oktober 2026')
            ->assertSee('Voorlijst 2026')
            ->assertSee('Drenthe')
            ->assertSee('Zuid-Holland')
            ->assertSee('50 plekken')
            ->assertSee('Smaak')
            ->assertSee('dinsdag en vrijdag om 12:00 uur')
            ->assertSee('maandag 21 december 2026')
            ->assertSee('Het panel')
            ->assertSee('Veelgestelde vragen')
            ->assertSee('Aanmeldformulier')
            ->assertSee('Aanmelden vanaf 12 oktober 2026');
    }

    #[Test]
    public function placeholder_blocks_are_hidden_unless_explicitly_enabled(): void
    {
        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);

        config()->set('site.placeholders', false);
        $this->get('/')->assertOk()->assertDontSee('CITAAT VOLGT')->assertDontSee('[BEELD');

        config()->set('site.placeholders', true);
        $this->get('/')->assertOk()->assertSee('CITAAT VOLGT')->assertSee('Wat deelnemers');
    }

    #[Test]
    public function navigation_only_links_to_existing_routes_or_homepage_anchors(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee(route('provincies.index'));
        $response->assertSee(route('bakkers.index'));
        $response->assertSee(route('aanmelden'));
        $response->assertSee(route('finale'));
        $response->assertSee(route('goede-doelen'));
        $response->assertSee(route('sponsoren'));
        $response->assertSee(route('pers'));
        $response->assertDontSee('Contact</a>');
    }

    #[Test]
    public function the_styleguide_is_only_available_when_enabled(): void
    {
        $this->seed(ProvinceSeeder::class);

        config()->set('site.styleguide_enabled', true);
        $this->get('/stijlgids')->assertOk()->assertSee('Het Register');

        config()->set('site.styleguide_enabled', false);
        $this->get('/stijlgids')->assertNotFound();
    }
}
