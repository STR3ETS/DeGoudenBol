<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use App\Support\Docs\HelpDocs;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpDocsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_manual_is_only_for_logged_in_staff(): void
    {
        $this->get('/docs')->assertRedirect('/admin/login');
        $this->get('/docs.json')->assertRedirect('/admin/login');
    }

    #[Test]
    public function staff_see_every_chapter_and_can_fetch_the_same_content_as_json(): void
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole(StaffRole::Communication->value);

        $this->actingAs($staff)->get('/docs')
            ->assertOk()
            ->assertSee('Handleiding')
            ->assertSee('Hoe zet ik de inschrijving open?')
            ->assertSee('Inrichten')
            ->assertSee('Problemen oplossen');

        $json = $this->actingAs($staff)->get('/docs.json')->assertOk()->json();

        $this->assertNotEmpty($json['secties']);
        $this->assertGreaterThan(80, count($json['vragen']));
        $this->assertSame('Hoe zet ik de inschrijving open?', collect($json['vragen'])->firstWhere('id', 'inschrijving-hoe-zet-ik-de-inschrijving-open')['vraag']);
    }

    #[Test]
    public function every_question_has_an_answer_and_a_unique_id(): void
    {
        $questions = HelpDocs::make()->questions();
        $ids = array_column($questions, 'id');

        $this->assertSame($ids, array_values(array_unique($ids)));

        foreach ($questions as $question) {
            $this->assertStringEndsWith('?', $question['vraag'].'?', 'Vragen eindigen met een vraagteken of zijn een korte probleemzin: '.$question['vraag']);
            $this->assertGreaterThan(40, strlen($question['antwoord']), 'Te kort antwoord bij: '.$question['vraag']);
        }
    }
}
