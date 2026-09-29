<?php

namespace Tests\Feature\Ranking;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\FreezeEdition;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\RespondToFinalInvitation;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Domain\Ranking\Notifications\FinalInvitationNotification;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Events\ResultFinalized;
use App\Domain\Testing\Models\Result;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class FreezeAndFinaleTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $first;

    private Entry $second;

    private User $intake;

    private User $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->first = $this->registered('Bakkerij Een', 'een@example.test', '11111111');
        $this->second = $this->registered('Bakkerij Twee', 'twee@example.test', '22222222');

        $this->intake = User::factory()->create();
        $this->intake->assignRole(StaffRole::Intake->value);
        $this->publisher = User::factory()->create();
        $this->publisher->assignRole(StaffRole::Publisher->value);
    }

    #[Test]
    public function tie_break_freeze_reveal_finalists_and_the_national_final_follow_the_rules(): void
    {
        Notification::fake();

        // Twee gelijke cijfers op plaats 1 → beslisronde nodig, bevriezing geblokkeerd.
        $this->publishProvincial([$this->first->getKey() => 8.0, $this->second->getKey() => 8.0]);

        $snapshot = RankingSnapshot::latestForProvince($this->edition->getKey(), $this->first->province_id);
        $this->assertTrue($snapshot->positions()->where('needs_tie_break', true)->count() === 2);

        $round = TieBreakRound::query()->where('province_id', $this->first->province_id)->firstOrFail();
        $this->assertSame(TieBreakStatus::Open, $round->status);
        $this->assertSame('position_1', $round->scope);
        $this->assertEqualsCanonicalizing([$this->first->getKey(), $this->second->getKey()], $round->entry_ids);

        $this->edition->forceFill(['status' => EditionStatus::Testing])->save();

        try {
            app(FreezeEdition::class)($this->edition, $this->publisher);
            $this->fail('Bevroren met open beslisronde');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Gelderland', $e->getMessage());
        }

        // Beslisronde: nieuwe monsters B01/B02, de uitkomst bepaalt alleen de volgorde.
        $this->finalizeRoundResult($this->first, SampleRound::TieBreak, 'B01', totalRaw: 78.0);
        $this->assertSame(TieBreakStatus::Open, $round->refresh()->status);
        $this->finalizeRoundResult($this->second, SampleRound::TieBreak, 'B02', totalRaw: 82.5);

        $round->refresh();
        $this->assertSame(TieBreakStatus::Decided, $round->status);
        $this->assertSame([$this->second->getKey(), $this->first->getKey()], $round->outcome_order);

        $resolved = RankingSnapshot::latestForProvince($this->edition->getKey(), $this->first->province_id);
        $this->assertSame([$this->second->getKey(), $this->first->getKey()], $resolved->positions()->pluck('entry_id')->all());
        $this->assertSame([1, 2], $resolved->positions()->pluck('position')->all());
        $this->assertSame(0, $resolved->positions()->where('needs_tie_break', true)->count());
        $this->assertSame(8.0, $resolved->positions()->first()->total, 'Het gepubliceerde cijfer blijft staan.');

        // Bevriezing: definitieve snapshot onder embargo, erkenningen met embargo, finalist uitgenodigd.
        app(FreezeEdition::class)($this->edition, $this->publisher);

        $this->assertSame(EditionStatus::Frozen, $this->edition->refresh()->status);
        $frozen = RankingSnapshot::frozenForProvince($this->edition->getKey(), $this->first->province_id);
        $this->assertNotNull($frozen);
        $this->assertNull($frozen->published_at);
        $this->assertSame($resolved->getKey(), RankingSnapshot::latestForProvince($this->edition->getKey(), $this->first->province_id)->getKey(), 'Het publiek ziet nog de voorlopige lijst.');

        $winnerRecognition = Recognition::query()->where('entry_id', $this->second->getKey())->where('type', RecognitionType::ProvinceWinner)->firstOrFail();
        $this->assertNotNull($winnerRecognition->embargo_until);
        $this->assertFalse($winnerRecognition->isActive());
        $this->assertTrue(Recognition::query()->where('entry_id', $this->first->getKey())->where('type', RecognitionType::Top10)->exists());
        $this->assertFalse(Recognition::query()->where('entry_id', $this->first->getKey())->where('type', RecognitionType::ProvinceWinner)->exists());

        $finalist = Finalist::query()->where('edition_id', $this->edition->getKey())->firstOrFail();
        $this->assertSame($this->second->getKey(), $finalist->entry_id);
        $this->assertSame(FinalistStatus::Invited, $finalist->status);
        Notification::assertSentTo($this->second->company->users()->first(), FinalInvitationNotification::class);

        $this->get(route('provincies.show', $this->first->province))
            ->assertOk()
            ->assertSee('Voorlopige Top')
            ->assertSee('provinciewinnaar van Gelderland:');

        // Reveal op de publicatiedag: per provincie, daarna is de editie gepubliceerd.
        foreach ($this->edition->provinces as $province) {
            $this->edition->provinces()->updateExistingPivot($province->getKey(), ['reveal_at' => now()->subMinute()]);
        }

        $this->artisan('publication:run')->assertSuccessful();

        $this->assertNotNull($frozen->refresh()->published_at);
        $this->assertSame(EditionStatus::Published, $this->edition->refresh()->status);
        $this->assertTrue($winnerRecognition->refresh()->isActive());

        $this->get(route('provincies.show', $this->first->province))
            ->assertOk()
            ->assertSee('Definitieve Top')
            ->assertSee('Provinciewinnaar 2026')
            ->assertDontSee('provinciewinnaar van Gelderland:');

        // Winnaar meldt zich af → plek naar nummer 2, titel blijft.
        app(RespondToFinalInvitation::class)($finalist, false);
        $this->assertSame(FinalistStatus::Declined, $finalist->refresh()->status);
        $replacement = Finalist::query()->where('entry_id', $this->first->getKey())->firstOrFail();
        $this->assertSame('next_in_line', $replacement->origin);
        $this->assertSame($replacement->getKey(), $finalist->replaced_by_id);
        $this->assertTrue(Recognition::query()->where('entry_id', $this->second->getKey())->where('type', RecognitionType::ProvinceWinner)->exists());

        $this->actingAs($this->first->company->users()->first(), 'participant')
            ->post(route('portaal.finale.reageren', $this->first->company), ['keuze' => 'bevestigen'])
            ->assertRedirect(route('portaal.uitslag', $this->first->company));
        $this->assertSame(FinalistStatus::Confirmed, $replacement->refresh()->status);

        // Finale: nieuw monster F01, landelijke lijst, erkenningen landelijk.
        $this->finalizeRoundResult($this->first, SampleRound::Final, 'F01', totalRaw: 86.0, total: 8.6);

        $batch = PublicationBatch::query()->where('status', BatchStatus::Draft)->firstOrFail();
        $item = $batch->items()->firstOrFail();
        $this->assertSame(SampleRound::Final, $item->round);
        $this->assertSame(ItemVisibility::Public, $item->visibility);

        $batch->forceFill(['status' => BatchStatus::Approved])->save();
        app(PublishBatch::class)($batch, $this->publisher);

        $this->assertSame(EntryStatus::Published, $this->first->refresh()->status);
        $national = RankingSnapshot::latestNational($this->edition->getKey());
        $this->assertNotNull($national);
        $this->assertSame([$this->first->getKey()], $national->positions()->pluck('entry_id')->all());
        $this->assertTrue(Recognition::query()->where('entry_id', $this->first->getKey())->where('type', RecognitionType::NationalWinner)->exists());

        $this->get(route('finale'))
            ->assertOk()
            ->assertSee('Bakkerij Een')
            ->assertSee('Top 5 van Nederland')
            ->assertSee('8,6');

        $this->get(route('edities.show', $this->edition))
            ->assertOk()
            ->assertSee('Gelderland')
            ->assertSee('Bakkerij Twee');
    }

    private function registered(string $name, string $email, string $kvk): Entry
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => $name, 'publicName' => $name, 'email' => $email, 'kvkNumber' => $kvk]));
        $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        return $entry;
    }

    /**
     * @param  array<int, float>  $totals  entry_id => cijfer
     */
    private function publishProvincial(array $totals): void
    {
        $batch = PublicationBatch::query()->create(['edition_id' => $this->edition->getKey(), 'scheduled_at' => now(), 'status' => BatchStatus::Approved]);

        foreach ($totals as $entryId => $total) {
            $batch->items()->create([
                'entry_id' => $entryId,
                'province_id' => $this->first->province_id,
                'round' => SampleRound::Provincial,
                'visibility' => ItemVisibility::Public,
                'result_snapshot' => ['total' => $total, 'total_raw' => $total * 10, 'card_count' => 6, 'criterion_averages' => ['smaak' => 20.0, 'structuur_luchtigheid' => 16.0, 'versheid' => 8.0, 'vulling_verhouding' => 12.0], 'scoring_model_version' => 1],
            ]);
        }

        app(PublishBatch::class)($batch, $this->publisher);
    }

    /**
     * Nieuw monster in een beslis- of finaleronde, gekoppeld via de kluis, met definitieve uitslag.
     */
    private function finalizeRoundResult(Entry $entry, SampleRound $round, string $number, float $totalRaw, ?float $total = null): void
    {
        $sample = Sample::factory()->create(['edition_id' => $this->edition->getKey(), 'round' => $round, 'sample_number' => $number, 'status' => SampleStatus::Final]);

        $this->actingAs($this->intake);
        app(VaultService::class)->link($sample->getKey(), $this->edition->getKey(), $entry->ulid, 'test '.$round->value);
        auth()->logout();

        $result = Result::query()->create([
            'sample_id' => $sample->getKey(),
            'scoring_model_version' => 1,
            'card_count' => 6,
            'criterion_averages' => ['smaak' => 20.0, 'structuur_luchtigheid' => 16.0, 'versheid' => 8.0, 'vulling_verhouding' => 12.0],
            'total_raw' => $totalRaw,
            'total' => $total ?? round($totalRaw / 10, 1),
            'flags' => ['missing_cards' => false, 'outliers' => []],
            'status' => ResultStatus::Final,
            'computed_at' => now(),
            'finalized_at' => now(),
        ]);

        event(new ResultFinalized($result));
    }
}
