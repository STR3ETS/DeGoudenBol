<?php

namespace Tests\Support;

use App\Domain\Intake\Actions\AssignSampleNumber;
use App\Domain\Intake\Actions\ReceiveSample;
use App\Domain\Intake\Data\IntakeData;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\AssignmentStatus;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Gedeelde opzet voor de testketen: editie 2026, één ontvangen en genummerd monster met
 * kluiskoppeling, een lopende sessie en een aantal panelleden met uitserveringen.
 */
trait BuildsTestChain
{
    use BuildsRegistrations;

    protected Entry $chainEntry;

    protected Sample $chainSample;

    protected TestSession $chainSession;

    /** @var list<Panelist> */
    protected array $chainPanelists = [];

    protected User $intakeUser;

    protected function setUpTestChain(int $panelists = 6, bool $running = true): void
    {
        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->intakeUser = User::factory()->create();
        $this->intakeUser->assignRole(StaffRole::Intake->value);

        $this->chainEntry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->chainEntry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        $this->actingAs($this->intakeUser);
        $sample = app(ReceiveSample::class)($this->chainEntry, new IntakeData(pieceCount: 8), $this->intakeUser);
        $this->chainSample = app(AssignSampleNumber::class)($sample, $this->intakeUser);
        auth()->logout();

        $factory = TestSession::factory()->state(['edition_id' => $this->edition->getKey()]);
        $this->chainSession = ($running ? $factory->running() : $factory)->create();
        $this->chainSession->samples()->attach($this->chainSample->getKey(), ['serving_order' => 1]);

        for ($i = 0; $i < $panelists; $i++) {
            $user = User::factory()->create();
            $user->assignRole(StaffRole::Panelist->value);
            $panelist = Panelist::factory()->create(['user_id' => $user->getKey(), 'edition_id' => $this->edition->getKey()]);
            $this->chainSession->panelists()->attach($panelist->getKey());
            $this->chainSession->assignments()->create([
                'panelist_id' => $panelist->getKey(),
                'sample_id' => $this->chainSample->getKey(),
                'serving_order' => 1,
                'status' => AssignmentStatus::Pending,
            ]);
            $this->chainPanelists[] = $panelist;
        }

        $this->chainSession->forceFill(['schedule_generated_at' => now()])->save();
    }

    /**
     * @return array<string, int>
     */
    protected function fullScores(int $smaak = 20): array
    {
        return [
            'smaak' => $smaak,
            'structuur_luchtigheid' => 16,
            'vulling_verhouding' => 12,
            'versheid' => 8,
            'korst_kleur' => 8,
            'bakgraad_vetopname' => 8,
            'geur' => 4,
            'uiterlijk_presentatie' => 4,
        ];
    }
}
