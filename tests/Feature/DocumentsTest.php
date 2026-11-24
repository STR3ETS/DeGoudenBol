<?php

namespace Tests\Feature;

use App\Domain\Marketing\Enums\PressMilestone;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Services\PressReleaseDocument;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Ranking\Actions\PublishPersonalReport;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

/**
 * Certificaat, rapport en persbericht als A4-document (sjablonen van 24 nov 2026): de organisatie maakt ze
 * vanuit de backoffice, de bakker opent zijn eigen certificaat en rapport in het portaal.
 */
class DocumentsTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->setUpTestChain();
    }

    #[Test]
    public function staff_make_certificates_for_confirmed_participants_and_the_baker_opens_his_own(): void
    {
        $entry = $this->chainEntry->fresh();
        $url = route('documenten.certificaten', ['ids' => $entry->getKey()]);

        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($this->staff(StaffRole::Reviewer))->get($url)->assertForbidden();

        $this->actingAs($this->staff(StaffRole::Intake))
            ->get($url)
            ->assertOk()
            ->assertSee('Certificaat van deelname')
            ->assertSee($entry->public_name)
            ->assertSee('heeft deelgenomen aan De Gouden Bol')
            ->assertSee('Namens de jury')
            ->assertSee('Download als pdf');

        // De naam bij "Namens de jury" is een editie-instelling; leeg blijft de regel open.
        $this->edition->forceFill(['settings' => $this->edition->settings->with(['certificate_signatory' => 'Voorzitter van de jury'])])->save();
        $this->actingAs($this->staff(StaffRole::Intake))->get($url)->assertOk()->assertSee('Voorzitter van de jury');

        // Zonder betaling geen certificaat.
        $entry->forceFill(['status' => EntryStatus::PendingPayment])->save();
        $this->actingAs($this->staff(StaffRole::Intake))->get($url)->assertNotFound();
        $entry->forceFill(['status' => EntryStatus::Registered])->save();

        auth('web')->logout();

        $this->actingAs($this->owner(), 'participant')
            ->get(route('portaal.oorkonde', $entry->company))
            ->assertOk()
            ->assertSee('Certificaat van deelname')
            ->assertSee($entry->public_name);
    }

    #[Test]
    public function the_report_follows_the_points_model_and_shows_the_number_instead_of_the_name(): void
    {
        $this->scoreAndFinalize();

        $report = ConfidentialReport::query()->where('entry_id', $this->chainEntry->getKey())->firstOrFail();
        $report->update(['strengths' => 'Luchtig en mooi goudbruin.', 'opportunities' => 'Iets meer krenten.']);
        $url = route('documenten.rapporten', ['ids' => $report->getKey()]);

        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($this->staff(StaffRole::Intake))->get($url)->assertForbidden();

        $response = $this->actingAs($this->staff(StaffRole::Reviewer))->get($url)->assertOk();

        $response
            ->assertSee('Concept')
            ->assertSee('Het 100-puntenmodel')
            ->assertSee('Acht onderdelen')
            ->assertSee('honderd punten')
            ->assertSee('Rapportcijfer')
            ->assertSee('Deelnemersnummer')
            ->assertSee($this->chainEntry->fresh()->delivery_code)
            ->assertSee('Provinciale ronde')
            ->assertSee('Vetopname en mondgevoel')
            ->assertSee('Wat al goud is')
            ->assertSee('Luchtig en mooi goudbruin.')
            ->assertSee('Op weg naar goud')
            ->assertSee('Iets meer krenten.');

        // "Naam eraf, nummer erop": de naam staat alleen in de balk op het scherm, niet op het vel.
        $sheet = Str::between($response->getContent(), '<article class="vel">', '</article>');
        $this->assertStringNotContainsString($this->chainEntry->public_name, $sheet);

        $this->assertSame(1, AuditLog::query()->where('action', 'report.documents_generated')->count());

        auth('web')->logout();

        // De bakker ziet zijn rapport pas na vrijgave, in dezelfde opmaak en zonder het woord concept.
        $this->actingAs($this->owner(), 'participant')->get(route('portaal.rapport', $this->chainEntry->company))->assertNotFound();

        app(PublishPersonalReport::class)($report->fresh(), $this->staff(StaffRole::Reviewer));

        $this->actingAs($this->owner(), 'participant')
            ->get(route('portaal.rapport', $this->chainEntry->company))
            ->assertOk()
            ->assertSee('Rapportcijfer')
            ->assertSee('Wat al goud is')
            ->assertDontSee('nog niet vrijgegeven');
    }

    #[Test]
    public function a_press_release_becomes_a_document_without_inventing_anything(): void
    {
        $release = PressRelease::query()->create([
            'edition_id' => $this->edition->getKey(),
            'milestone' => PressMilestone::NationalFinal,
            'title' => 'De landelijke lijst van De Gouden Bol is bekend',
            'body' => implode("\n\n", [
                'PERSBERICHT – ONDER EMBARGO TOT MAANDAG 21 DECEMBER 2026, 12:00 UUR',
                'Arnhem, 21 december 2026 – De landelijke lijst is bekend.',
                "Over de keuring\nIeder monster is blind beoordeeld.",
                "Noot voor de redactie (niet voor publicatie)\nContact: [PERSCONTACT VOLGT]",
            ]),
            'embargo_until' => now()->addDay(),
        ]);

        $document = PressReleaseDocument::for($release);

        $this->assertSame('Arnhem', $document->place);
        $this->assertSame('21 december 2026', $document->date);
        $this->assertSame('De landelijke lijst is bekend.', $document->lead);
        $this->assertSame([['heading' => 'Over de keuring', 'text' => 'Ieder monster is blind beoordeeld.']], $document->blocks);
        $this->assertNotNull($document->embargo);

        $url = route('documenten.persbericht', ['pressRelease' => $release->getKey()]);

        $this->get($url)->assertRedirect('/admin/login');
        $this->actingAs($this->staff(StaffRole::Intake))->get($url)->assertForbidden();

        $this->actingAs($this->staff(StaffRole::Communication))
            ->get($url)
            ->assertOk()
            ->assertSee('De landelijke lijst van De Gouden Bol is bekend')
            ->assertSee('ARNHEM')
            ->assertSee('21 DECEMBER 2026')
            ->assertSee('Onder embargo tot')
            ->assertSee('Over de keuring')
            ->assertSee('Einde persbericht')
            ->assertSee('Noot voor de redactie')
            // Geen citaat ingevuld: geen citaatkader. Een placeholder als contact komt niet in het document.
            ->assertDontSee('pers__citaat"', false)
            ->assertDontSee('[PERSCONTACT VOLGT]');

        $release->update(['subtitle' => 'Twaalf provinciewinnaars in de finale', 'quote' => 'Voorbeeldcitaat uit de test.', 'quote_name' => 'Testpersoon', 'quote_role' => 'woordvoerder']);

        $this->actingAs($this->staff(StaffRole::Communication))
            ->get($url)
            ->assertOk()
            ->assertSee('Twaalf provinciewinnaars in de finale')
            ->assertSee('Voorbeeldcitaat uit de test.')
            ->assertSee('Testpersoon, woordvoerder');

        auth('web')->logout();

        // Voor iedereen pas na publicatie.
        $this->get(route('pers.document', $release))->assertNotFound();

        $release->forceFill(['published_at' => now()->subMinute(), 'embargo_until' => now()->subMinute()])->save();

        $this->get(route('pers.document', $release))->assertOk()->assertSee('Einde persbericht')->assertDontSee('Onder embargo tot');
        $this->get(route('pers.toon', $release))->assertOk()->assertSee(route('pers.document', $release));
    }

    private function staff(StaffRole $role): User
    {
        $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $user->assignRole($role->value);

        return $user;
    }

    private function owner(): mixed
    {
        return $this->chainEntry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();
    }

    private function scoreAndFinalize(): void
    {
        foreach ($this->chainPanelists as $panelist) {
            $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), 'Luchtig en krokant', 'Iets meer krenten', CarbonImmutable::now()));
        }

        app(FinalizeResult::class)($this->chainSample->refresh(), $this->staff(StaffRole::Reviewer));
        $this->chainEntry->refresh();
    }
}
