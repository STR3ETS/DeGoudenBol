<?php

namespace Tests\Feature;

use App\Domain\Participants\Events\ProfileSubmitted;
use App\Domain\Participants\Models\Profile;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Events\BatchSubmitted;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Meldingen in de bel van de backoffice: per rol, en nooit naar de veroorzaker zelf.
 */
class StaffNotificationsTest extends TestCase
{
    use BuildsRegistrations;
    use RefreshDatabase;

    #[Test]
    public function a_submitted_batch_notifies_the_other_publishers_but_not_the_submitter(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        [$submitter, $colleague, $other] = User::factory()->count(3)->create()->each(fn (User $user) => $user->assignRole(StaffRole::Publisher->value));
        $finance = User::factory()->create();
        $finance->assignRole(StaffRole::Finance->value);

        $batch = PublicationBatch::query()->create([
            'edition_id' => $this->edition->getKey(),
            'scheduled_at' => now()->addDay(),
            'status' => BatchStatus::PendingApproval,
            'submitted_by' => $submitter->getKey(),
            'submitted_at' => now(),
        ]);

        BatchSubmitted::dispatch($batch, $submitter);

        $this->assertSame(1, $this->notificationsFor($colleague));
        $this->assertSame(1, $this->notificationsFor($other));
        $this->assertSame(0, $this->notificationsFor($submitter));
        $this->assertSame(0, $this->notificationsFor($finance));
        $this->assertStringContainsString('wacht op goedkeuring', (string) DB::table('notifications')->value('data'));
    }

    #[Test]
    public function a_submitted_profile_text_notifies_communication_only(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $communication = User::factory()->create();
        $communication->assignRole(StaffRole::Communication->value);
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole(StaffRole::Communication->value);
        $publisher = User::factory()->create();
        $publisher->assignRole(StaffRole::Publisher->value);

        $profile = Profile::factory()->create();

        ProfileSubmitted::dispatch($profile);

        $this->assertSame(1, $this->notificationsFor($communication));
        $this->assertSame(0, $this->notificationsFor($inactive));
        $this->assertSame(0, $this->notificationsFor($publisher));
    }

    private function notificationsFor(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->count();
    }
}
