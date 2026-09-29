<?php

namespace Tests\Feature\Panel;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\PanelistConflict;
use App\Domain\Testing\Models\Scorecard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

class PanelAppTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestChain(panelists: 2);
    }

    #[Test]
    public function only_active_panelists_can_open_the_panel_app_and_the_backoffice_stays_closed_to_them(): void
    {
        $this->get(route('panel.overzicht'))->assertRedirect(route('panel.inloggen'));

        $reviewer = User::factory()->create();
        $reviewer->assignRole(StaffRole::Reviewer->value);
        $this->actingAs($reviewer)->get(route('panel.overzicht'))->assertForbidden();

        $panelistUser = User::query()->findOrFail($this->chainPanelists[0]->user_id);
        $this->actingAs($panelistUser)->get(route('panel.overzicht'))->assertOk()->assertSee($this->chainPanelists[0]->display_code)->assertSee('0001');
        $this->actingAs($panelistUser)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function a_panelist_sees_the_scorecard_submits_it_through_the_api_and_never_sees_the_content_again(): void
    {
        $panelist = $this->chainPanelists[0];
        $user = User::query()->findOrFail($panelist->user_id);
        $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();

        $this->actingAs($user)
            ->get(route('panel.monster', $assignment))
            ->assertOk()
            ->assertSee('Smaak')
            ->assertSee('Indienen en vergrendelen')
            ->assertSee('data-panel-scorecard', false);

        $uuid = (string) Str::uuid();
        $payload = ['cards' => [[
            'uuid' => $uuid,
            'assignment_id' => $assignment->getKey(),
            'scores' => $this->fullScores(),
            'strengths' => 'Krokant',
            'opportunities' => null,
            'submitted_at' => now()->toIso8601String(),
        ]]];

        $this->actingAs($user)->postJson(route('panel.api.scorekaarten'), $payload)
            ->assertOk()
            ->assertJsonPath("results.{$uuid}.status", 'accepted')
            ->assertJsonPath("results.{$uuid}.sample", '0001');

        $this->actingAs($user)->postJson(route('panel.api.scorekaarten'), $payload)
            ->assertOk()
            ->assertJsonPath("results.{$uuid}.status", 'duplicate');

        $this->assertSame(1, Scorecard::query()->count());

        $this->actingAs($user)
            ->get(route('panel.monster', $assignment))
            ->assertOk()
            ->assertSee('vergrendeld')
            ->assertDontSee('Krokant')
            ->assertDontSee('data-panel-scorecard', false);

        $this->actingAs($user)->getJson(route('panel.api.schema'))
            ->assertOk()
            ->assertJsonPath('sessions.0.assignments.0.submitted', true)
            ->assertJsonMissing(['strengths' => 'Krokant']);

        // Een ander panellid mag dit monster niet zien.
        $other = User::query()->findOrFail($this->chainPanelists[1]->user_id);
        $this->actingAs($other)->get(route('panel.monster', $assignment))->assertForbidden();
    }

    #[Test]
    public function the_form_fallback_without_javascript_also_submits(): void
    {
        $panelist = $this->chainPanelists[1];
        $user = User::query()->findOrFail($panelist->user_id);
        $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();

        $this->actingAs($user)
            ->post(route('panel.monster.indienen', $assignment), ['uuid' => (string) Str::uuid(), 'scores' => $this->fullScores(smaak: 30)])
            ->assertSessionHasErrors('scores');

        $this->actingAs($user)
            ->post(route('panel.monster.indienen', $assignment), ['uuid' => (string) Str::uuid(), 'scores' => $this->fullScores()])
            ->assertRedirect(route('panel.overzicht', ['ingediend' => '0001']));
    }

    #[Test]
    public function a_panelist_reports_and_withdraws_a_conflict_of_interest(): void
    {
        $user = User::query()->findOrFail($this->chainPanelists[0]->user_id);

        $this->actingAs($user)->get(route('panel.conflicten', ['q' => 'Testers']))->assertOk()->assertSee('Bakkerij Testers')->assertSee('Melden');

        $this->actingAs($user)->post(route('panel.conflicten.toevoegen'), ['company_id' => $this->chainEntry->company_id])->assertRedirect(route('panel.conflicten'));
        $conflict = PanelistConflict::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertSame($this->chainEntry->company_id, $conflict->company_id);

        $this->actingAs($user)->delete(route('panel.conflicten.verwijderen', $conflict))->assertRedirect();
        $this->assertSame(0, PanelistConflict::query()->count());
    }
}
