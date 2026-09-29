<?php

namespace Tests\Feature\Marketing;

use App\Domain\Marketing\Actions\SendPressKit;
use App\Domain\Marketing\Enums\PressMilestone;
use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Notifications\PressKitNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\SnapshotStatus;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Events\ProvinceRevealed;
use App\Domain\Ranking\Models\RankingSnapshot;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class PressReleaseTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $first;

    private Entry $second;

    private RankingSnapshot $snapshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->first = $this->registered('Bakkerij Een', 'een@example.test', '11111111');
        $this->second = $this->registered('Bakkerij Twee', 'twee@example.test', '22222222');

        $this->edition->forceFill(['main_publication_at' => now()->addDay()])->save();

        $this->snapshot = RankingSnapshot::query()->create([
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

        $this->snapshot->positions()->create(['entry_id' => $this->first->getKey(), 'position' => 1, 'total' => 8.4, 'label' => 'new', 'needs_tie_break' => false]);
        $this->snapshot->positions()->create(['entry_id' => $this->second->getKey(), 'position' => 2, 'total' => 8.1, 'label' => 'new', 'needs_tie_break' => false]);
    }

    #[Test]
    public function a_press_release_is_generated_under_embargo_sent_to_regional_media_and_published_on_reveal(): void
    {
        Notification::fake();

        EditionFrozen::dispatch($this->edition->fresh());

        $release = PressRelease::query()->where('province_id', $this->first->province_id)->where('milestone', PressMilestone::ProvinceTop10)->firstOrFail();
        $this->assertStringContainsString('Bakkerij Een', $release->title);
        $this->assertStringContainsString('ONDER EMBARGO', $release->body);
        $this->assertStringContainsString('Bakkerij Twee', $release->body);
        $this->assertStringContainsString('8,4', $release->body);
        $this->assertNull($release->published_at);
        $this->assertTrue($release->embargo_until->isFuture());

        // Nog eens bevriezen → geen dubbel bericht, tekst blijft staan.
        EditionFrozen::dispatch($this->edition->fresh());
        $this->assertSame(1, PressRelease::query()->count());

        // Onder embargo: niet op /pers, niet openbaar, perskit alleen via ondertekende link.
        $this->get(route('pers'))->assertOk()->assertDontSee($release->title);
        $this->get(route('pers.toon', $release))->assertNotFound();
        $this->get('/pers/kit/'.$release->slug)->assertForbidden();

        $signed = URL::temporarySignedRoute('pers.kit', now()->addDay(), ['pressRelease' => $release->slug]);
        $this->get($signed)->assertOk()->assertSee('ONDER EMBARGO')->assertSee($release->title);

        // Perskit naar de regionale en landelijke contacten, niet naar andere provincies.
        $otherProvince = $this->edition->provinces->firstWhere('id', '!=', $this->first->province_id);
        MediaContact::query()->create(['name' => 'Regionaal', 'outlet' => 'Regiokrant', 'email' => 'regio@example.test', 'province_id' => $this->first->province_id]);
        MediaContact::query()->create(['name' => 'Landelijk', 'outlet' => 'Persbureau', 'email' => 'landelijk@example.test', 'province_id' => null]);
        MediaContact::query()->create(['name' => 'Elders', 'outlet' => 'Andere krant', 'email' => 'elders@example.test', 'province_id' => $otherProvince->getKey()]);

        $this->assertSame(2, app(SendPressKit::class)($release));
        Notification::assertSentOnDemandTimes(PressKitNotification::class, 2);
        $this->assertNotNull($release->fresh()->sent_at);

        // Reveal → gepubliceerd, embargo-kop weg, zichtbaar op /pers en op de provinciepagina.
        ProvinceRevealed::dispatch($this->edition->fresh(), $this->first->province, $this->snapshot);

        $release->refresh();
        $this->assertTrue($release->isPublished());
        $this->assertStringNotContainsString('ONDER EMBARGO', $release->body);

        $this->get(route('pers'))->assertOk()->assertSee($release->title);
        $this->get(route('pers.toon', $release))->assertOk()->assertSee('Bakkerij Twee')->assertDontSee('ONDER EMBARGO');
        $this->get($signed)->assertRedirect(route('pers.toon', $release));
        $this->get(route('provincies.show', $this->first->province))->assertOk()->assertSee('Persbericht')->assertSee($release->title);
    }

    private function registered(string $name, string $email, string $kvk): Entry
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => $name, 'publicName' => $name, 'email' => $email, 'kvkNumber' => $kvk]));
        $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        return $entry;
    }
}
