<?php

namespace Tests\Feature;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_chains_every_entry_to_the_previous_hash(): void
    {
        $logger = app(AuditLogger::class);
        $actor = User::factory()->create();
        $edition = Edition::factory()->create();

        $first = $logger->record('edition.created', $edition, ['year' => $edition->year], $actor);
        $second = $logger->record('edition.settings_changed', $edition, ['capacity_per_province' => [50, 40]], $actor);

        $this->assertNull($first->prev_hash);
        $this->assertSame($first->hash, $second->prev_hash);
        $this->assertSame(64, strlen($second->hash));
        $this->assertSame($actor->id, $second->actor_id);

        $this->assertTrue($logger->verify()['ok']);
        $this->assertSame(2, $logger->verify()['checked']);
        $this->assertSame($second->hash, $logger->latestHash());
    }

    #[Test]
    public function tampering_with_a_row_breaks_the_chain(): void
    {
        $logger = app(AuditLogger::class);

        $logger->record('score.finalized', null, ['sample' => '0001', 'total' => 8.4]);
        $target = $logger->record('score.finalized', null, ['sample' => '0002', 'total' => 7.9]);
        $logger->record('score.finalized', null, ['sample' => '0003', 'total' => 9.1]);

        DB::table('audit_logs')->where('id', $target->id)->update([
            'payload' => json_encode(['sample' => '0002', 'total' => 9.9]),
        ]);

        $result = $logger->verify();

        $this->assertFalse($result['ok']);
        $this->assertSame($target->id, $result['broken_id']);
    }

    #[Test]
    public function deleting_a_row_breaks_the_chain(): void
    {
        $logger = app(AuditLogger::class);

        $logger->record('a');
        $middle = $logger->record('b');
        $logger->record('c');

        DB::table('audit_logs')->where('id', $middle->id)->delete();

        $this->assertFalse($logger->verify()['ok']);
    }

    #[Test]
    public function entries_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $entry = app(AuditLogger::class)->record('a');

        try {
            $entry->update(['action' => 'b']);
            $this->fail('Bijwerken had moeten falen.');
        } catch (LogicException) {
            $this->assertSame('a', $entry->fresh()->action);
        }

        $this->expectException(LogicException::class);

        $entry->delete();
    }

    #[Test]
    public function the_verify_command_reports_the_chain_state(): void
    {
        app(AuditLogger::class)->record('a');

        $this->artisan('audit:verify')->assertSuccessful();

        AuditLog::withoutEvents(fn () => DB::table('audit_logs')->update(['hash' => str_repeat('0', 64)]));

        $this->artisan('audit:verify')->assertFailed();
    }
}
