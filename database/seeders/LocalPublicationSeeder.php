<?php

namespace Database\Seeders;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\ApproveBatch;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\SubmitBatch;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\ServingAssignment;
use App\Domain\Testing\Models\TestSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Alleen lokaal: laat het eerste demo-monster de hele keten doorlopen (zes kaarten, definitief,
 * batch met twee goedkeuringen, publicatie) zodat Voorlijst, profiel en portaal een uitslag tonen.
 * Idempotent: draait alleen als er nog geen gepubliceerde batch is.
 */
class LocalPublicationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || PublicationBatch::query()->where('status', BatchStatus::Published)->exists()) {
            return;
        }

        $session = TestSession::query()->where('name', '[DEMO] Sessie A')->first();
        $sample = Sample::query()->whereNotNull('sample_number')->orderBy('sample_number')->first();

        if ($session === null || $sample === null) {
            return;
        }

        $assignments = ServingAssignment::query()->where('test_session_id', $session->getKey())->where('sample_id', $sample->getKey())->with('panelist')->get();

        foreach ($assignments as $index => $assignment) {
            if ($assignment->findScorecard() !== null) {
                continue;
            }

            app(SubmitScorecard::class)($assignment->panelist, new ScorecardSubmission(
                uuid: (string) Str::uuid(),
                assignmentId: $assignment->getKey(),
                scores: [
                    'smaak' => 19 + ($index % 3),
                    'structuur_luchtigheid' => 15 + ($index % 2),
                    'vulling_verhouding' => 11 + ($index % 2),
                    'versheid' => 8,
                    'korst_kleur' => 7 + ($index % 2),
                    'bakgraad_vetopname' => 8,
                    'geur' => 4,
                    'uiterlijk_presentatie' => 4,
                ],
                strengths: ['Mooi krokant korstje', 'Goede verhouding krenten en rozijnen', 'Luchtig deeg'][$index % 3],
                opportunities: ['Iets minder vet', 'Vulling mag gelijkmatiger', null][$index % 3],
                submittedAt: CarbonImmutable::now(),
            ));
        }

        $reviewer = User::query()->where('email', 'reviewer@degoudenbol.test')->firstOrFail();
        app(FinalizeResult::class)($sample->refresh(), $reviewer);

        $batch = PublicationBatch::query()->where('status', BatchStatus::Draft)->firstOrFail();

        $publishers = collect(['publisher@degoudenbol.test', '[DEMO] Publicatie 2' => 'publisher2@degoudenbol.test', '[DEMO] Publicatie 3' => 'publisher3@degoudenbol.test'])
            ->map(function (string $email, int|string $name): User {
                $user = User::query()->firstOrCreate(['email' => $email], ['name' => is_string($name) ? $name : 'Publicatie', 'password' => 'password', 'is_active' => true]);

                if (! $user->hasRole(StaffRole::Publisher->value)) {
                    $user->assignRole(StaffRole::Publisher->value);
                }

                return $user;
            })
            ->values();

        app(SubmitBatch::class)($batch, $publishers[0]);
        app(ApproveBatch::class)($batch, $publishers[1]);
        app(ApproveBatch::class)($batch, $publishers[2]);
        app(PublishBatch::class)($batch->refresh(), $publishers[0]);
    }
}
