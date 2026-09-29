<?php

namespace Tests\Feature\Marketing;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Marketing\Models\ShareEvent;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Events\EntryRegistered;
use App\Domain\Participants\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class RecognitionTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();
    }

    #[Test]
    public function a_confirmed_participation_grants_the_participant_recognition_with_a_badge_and_verification_page(): void
    {
        event(new EntryRegistered($this->entry));

        $recognition = Recognition::query()->where('entry_id', $this->entry->getKey())->where('type', RecognitionType::Participant)->firstOrFail();
        $this->assertSame(10, strlen($recognition->code));
        $this->assertSame('2026-12-21', $recognition->valid_until->toDateString());
        $this->assertTrue($recognition->isActive());

        $this->get(route('erkenning.toon', $recognition))
            ->assertOk()
            ->assertSee('Geverifieerde erkenning')
            ->assertSee('Bakkerij Testers')
            ->assertSee('Deelnemer De Gouden Bol 2026')
            ->assertSee($recognition->code);

        $badge = $this->get(route('erkenning.badge', ['recognition' => $recognition, 'variant' => 'donker']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->assertStringContainsString('<svg', $badge->getContent());
        $this->assertStringContainsString('Deelnemer', $badge->getContent());
        $this->assertStringContainsString('#1C0F03', $badge->getContent());

        $this->get(route('erkenning.zoek', ['code' => strtolower($recognition->code)]))->assertRedirect(route('erkenning.toon', $recognition));
        $this->get(route('erkenning.zoek', ['code' => 'ONBEKEND1']))->assertRedirect(route('erkenning.zoek'))->assertSessionHasErrors('code');
    }

    #[Test]
    public function embargoed_or_revoked_recognitions_are_not_verifiable_and_historic_ones_say_so(): void
    {
        $embargoed = Recognition::query()->create([
            'entry_id' => $this->entry->getKey(), 'company_id' => $this->entry->company_id, 'edition_id' => $this->edition->getKey(), 'province_id' => $this->entry->province_id,
            'type' => RecognitionType::ProvinceWinner, 'status' => 'active', 'valid_from' => now()->toDateString(), 'embargo_until' => now()->addDay(),
        ]);
        $this->get(route('erkenning.toon', $embargoed))->assertNotFound();
        $this->get(route('erkenning.badge', $embargoed))->assertNotFound();

        $historic = Recognition::query()->create([
            'entry_id' => $this->entry->getKey(), 'company_id' => $this->entry->company_id, 'edition_id' => $this->edition->getKey(),
            'type' => RecognitionType::Tested, 'status' => 'active', 'valid_from' => now()->subYear()->toDateString(), 'valid_until' => now()->subDay()->toDateString(),
        ]);
        $this->get(route('erkenning.toon', $historic))->assertOk()->assertSee('Historische erkenning');

        $historic->forceFill(['status' => 'revoked'])->save();
        $this->get(route('erkenning.toon', $historic))->assertNotFound();
    }

    #[Test]
    public function the_portal_marketing_page_offers_badges_texts_images_and_measures_shares(): void
    {
        event(new EntryRegistered($this->entry));
        $owner = $this->entry->company->users()->first();
        $recognition = Recognition::query()->where('entry_id', $this->entry->getKey())->firstOrFail();

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.marketing'))
            ->assertOk()
            ->assertSee('Bevestigde deelname')
            ->assertSee('erkenning/'.$recognition->code.'/badge.svg')
            ->assertSee('#DeGoudenBol')
            ->assertSee(route('bakkers.toon', $this->entry->company))
            ->assertSee('Alles als zip');

        $image = $this->actingAs($owner, 'participant')
            ->get(route('portaal.marketing.beeld', [$this->entry->company, 'participant', 'story']))
            ->assertOk();
        $this->assertStringContainsString('width="1080" height="1920"', $image->getContent());
        $this->assertStringContainsString('Bakkerij Testers', $image->getContent());

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.marketing.beeld', [$this->entry->company, 'province_winner', 'feed']))
            ->assertNotFound();

        $zip = $this->actingAs($owner, 'participant')
            ->get(route('portaal.marketing.kit', [$this->entry->company, 'participant']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');
        $this->assertStringStartsWith('PK', $zip->getContent());

        $this->actingAs($owner, 'participant')
            ->postJson(route('portaal.marketing.meting', $this->entry->company), ['milestone' => 'participant', 'kind' => 'share'])
            ->assertOk();

        $this->assertSame(2, ShareEvent::query()->count());
        $this->assertSame('download', ShareEvent::query()->orderBy('id')->first()->kind);
    }
}
