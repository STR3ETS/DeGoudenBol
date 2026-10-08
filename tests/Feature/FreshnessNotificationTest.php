<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

/**
 * Versheidswaarschuwing in de bel: alleen in het venster vlak voor het verlopen, en alleen voor
 * monsters die nog niet beoordeeld zijn.
 */
class FreshnessNotificationTest extends TestCase
{
    use BuildsTestChain;
    use RefreshDatabase;

    #[Test]
    public function coordination_is_warned_half_an_hour_before_a_sample_expires(): void
    {
        $this->setUpTestChain(panelists: 1);

        $coordinator = User::factory()->create();
        $coordinator->assignRole(StaffRole::Coordinator->value);
        $finance = User::factory()->create();
        $finance->assignRole(StaffRole::Finance->value);

        // Het monster is net ontvangen: de versheid loopt nog uren. Geen waarschuwing.
        $this->artisan('freshness:notify')->assertSuccessful();
        $this->assertSame(0, $this->notificationsFor($coordinator));

        // Nog 25 minuten: binnen het venster van 20 tot 30 minuten voor het verlopen.
        $this->chainSample->intake->forceFill(['freshness_expires_at' => now()->addMinutes(25)])->save();

        $this->artisan('freshness:notify')->assertSuccessful();

        $this->assertSame(1, $this->notificationsFor($coordinator));
        $this->assertSame(0, $this->notificationsFor($finance));
        $this->assertStringContainsString('Monster '.$this->chainSample->label().' verloopt', (string) DB::table('notifications')->value('data'));
    }

    private function notificationsFor(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->count();
    }
}
