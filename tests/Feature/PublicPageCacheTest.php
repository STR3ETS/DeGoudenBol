<?php

namespace Tests\Feature;

use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\PublicCache;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);
        config()->set('publiccache.enabled', true);
    }

    #[Test]
    public function anonymous_public_pages_are_cached_and_invalidated_when_content_changes(): void
    {
        $this->get('/nieuws')->assertOk()->assertHeader('X-Public-Cache', 'MISS')->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=600');
        $this->get('/nieuws')->assertOk()->assertHeader('X-Public-Cache', 'HIT');

        // Een nieuw bericht verhoogt de generatie: de volgende aanvraag is vers.
        NewsPost::query()->create(['title' => 'Testbericht over de keuring', 'body' => '<p>Tekst</p>', 'published_at' => now()->subMinute()]);

        $this->get('/nieuws')->assertOk()->assertHeader('X-Public-Cache', 'MISS')->assertSee('Testbericht over de keuring');
        $this->get('/nieuws')->assertOk()->assertHeader('X-Public-Cache', 'HIT')->assertSee('Testbericht over de keuring');

        // Handmatig verversen (deploy) werkt ook.
        PublicCache::bump();
        $this->get('/nieuws')->assertOk()->assertHeader('X-Public-Cache', 'MISS');
    }

    #[Test]
    public function cached_pages_never_serve_logged_in_users_forms_or_unlisted_routes(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Public-Cache', 'MISS');
        $this->get('/aanmelden')->assertOk()->assertHeaderMissing('X-Public-Cache');
        $this->get('/portaal/inloggen')->assertOk()->assertHeaderMissing('X-Public-Cache');

        // Het nieuwsbriefformulier op gecachte pagina's werkt zonder sessietoken; de honeypot blokkeert bots.
        $this->post('/nieuwsbrief', ['email' => 'lezer@example.test', 'bedrijfsnaam' => 'bot'])->assertSessionHasErrors('bedrijfsnaam');

        $participant = ParticipantUser::factory()->create();
        $this->actingAs($participant, 'participant')->get('/')->assertOk()->assertHeaderMissing('X-Public-Cache');

        config()->set('publiccache.enabled', false);
        $this->get('/')->assertOk()->assertHeaderMissing('X-Public-Cache');
    }
}
