<?php

namespace Tests\Feature;

use App\Support\Site\MachineContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * De blogs en landingspagina's van de contentmachine staan in EazyDash; de site haalt ze op met
 * het leestoken en toont ze in de eigen omlijsting.
 */
class MachineContentTest extends TestCase
{
    use RefreshDatabase;

    private const string API = 'https://eazy.test/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.eazyonline' => ['url' => self::API, 'token' => 'leestoken']]);
    }

    /** @return array<string, mixed> */
    private function stuk(string $type, string $slug, string $titel): array
    {
        return [
            'id' => 1, 'external_id' => null, 'type' => $type, 'title' => $titel, 'slug' => $slug, 'excerpt' => 'Zo doe je dat.',
            'featured_image' => 'https://eazy.test/storage/cms-assets/bol.jpg', 'featured_image_alt' => 'Een oliebol',
            'meta_title' => $titel.' | De Gouden Bol', 'meta_description' => 'Zo doe je dat, in het kort.',
            'published_at' => '2026-10-30T10:00:00+01:00', 'updated_at' => '2026-10-30T10:00:00+01:00', 'author' => null,
        ];
    }

    private function nepApi(): void
    {
        Http::fake([
            self::API.'/content/oliebollen-opwarmen' => Http::response(['data' => $this->stuk('blog', 'oliebollen-opwarmen', 'Oliebollen opwarmen') + [
                'content' => '<section class="sec"><h1 class="hero-h1">Oliebollen opwarmen</h1><p onclick="stuur()">In de oven.</p></section><script>alert(1)</script>',
            ]]),
            self::API.'/content/beste-oliebollen-arnhem' => Http::response(['data' => $this->stuk('service', 'beste-oliebollen-arnhem', 'Beste oliebollen Arnhem') + [
                'content' => '<style>.lp-h1{color:#fff}</style><section class="lp-hero"><h1 class="lp-h1">Beste oliebollen in Arnhem</h1></section>',
            ]]),
            self::API.'/content*' => Http::response([
                'data' => [$this->stuk('blog', 'oliebollen-opwarmen', 'Oliebollen opwarmen'), $this->stuk('service', 'beste-oliebollen-arnhem', 'Beste oliebollen Arnhem')],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    #[Test]
    public function het_blogoverzicht_toont_de_artikelen_en_geen_landingspaginas(): void
    {
        $this->nepApi();

        $this->get('/blog')->assertOk()
            ->assertSee('Oliebollen opwarmen')
            ->assertSee('/blog/oliebollen-opwarmen', false)
            ->assertSee('30 oktober 2026')
            ->assertDontSee('Beste oliebollen Arnhem');

        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer leestoken'));
    }

    #[Test]
    public function een_artikel_staat_in_de_omlijsting_van_de_site_zonder_scripts(): void
    {
        $this->nepApi();

        $this->get('/blog/oliebollen-opwarmen')->assertOk()
            ->assertSee('<title>Oliebollen opwarmen | De Gouden Bol</title>', false)
            ->assertSee('<meta name="description" content="Zo doe je dat, in het kort.">', false)
            ->assertSee('<h1 class="hero-h1">Oliebollen opwarmen</h1>', false)
            ->assertSee('<header class="nav"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('[aria-controls^="faq-answer-"]', false)
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onclick', false);
    }

    #[Test]
    public function een_landingspagina_staat_direct_onder_het_domein_met_zijn_eigen_opmaak(): void
    {
        $this->nepApi();

        $this->get('/beste-oliebollen-arnhem')->assertOk()
            ->assertSee('<style>.lp-h1{color:#fff}</style>', false)
            ->assertSee('Beste oliebollen in Arnhem');
    }

    #[Test]
    public function een_artikel_is_geen_landingspagina_en_andersom(): void
    {
        $this->nepApi();

        $this->get('/oliebollen-opwarmen')->assertNotFound();
        $this->get('/blog/beste-oliebollen-arnhem')->assertNotFound();
    }

    #[Test]
    public function een_onbekend_adres_blijft_een_404(): void
    {
        $this->nepApi();

        $this->get('/bestaat-niet')->assertNotFound();
        $this->get('/blog/bestaat-niet')->assertNotFound();
    }

    #[Test]
    public function een_vaste_pagina_gaat_voor(): void
    {
        $this->nepApi();

        $this->get('/bakkers-academie')->assertOk();
        Http::assertNothingSent();
    }

    #[Test]
    public function de_paginas_staan_in_de_sitemap(): void
    {
        $this->nepApi();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(url('/blog'), false)
            ->assertSee(url('/blog/oliebollen-opwarmen'), false)
            ->assertSee(url('/beste-oliebollen-arnhem'), false);
    }

    #[Test]
    public function het_menu_verwijst_naar_het_blog(): void
    {
        $this->nepApi();

        $this->get('/blog')->assertSee('<li><a href="/blog">Blog</a></li><li><a href="/pers">', false)
            ->assertSee('<a href="/sponsoring">Sponsoring</a></li><li><a href="/blog">Blog</a></li></ul>', false);
    }

    #[Test]
    public function zonder_token_blijft_de_site_zoals_hij_was(): void
    {
        config(['services.eazyonline.token' => null]);
        Http::fake();

        $this->get('/blog')->assertOk()->assertSee('Er staan nog geen artikelen');
        $this->get('/blog/oliebollen-opwarmen')->assertNotFound();
        $this->get('/beste-oliebollen-arnhem')->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function valt_de_api_weg_dan_doen_de_vaste_paginas_het_gewoon(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 500)]);

        $this->get('/blog')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
    }

    #[Test]
    public function schoon_haalt_alleen_scripts_en_gebeurtenissen_weg(): void
    {
        $uit = MachineContent::schoon('<p onclick="x()" class="a">online = goed</p><a href="javascript:x()">b</a><script>y()</script><style>.a{}</style>');

        $this->assertSame('<p class="a">online = goed</p><a href="#">b</a><style>.a{}</style>', $uit);
    }
}
