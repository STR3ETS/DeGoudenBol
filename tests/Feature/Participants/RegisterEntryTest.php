<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Domain\Platform\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class RegisterEntryTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function it_creates_account_company_location_profile_acceptance_entry_and_order_in_one_go(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());

        $this->assertSame(EntryStatus::PendingPayment, $entry->status);
        $this->assertNotNull($entry->reservation_expires_at);
        $this->assertSame('Bakkerij Testers', $entry->public_name);
        $this->assertSame(['gluten', 'melk'], $entry->allergens);

        $company = $entry->company;
        $this->assertSame('bakkerij-testers', $company->slug);
        $this->assertSame('12345678', $company->kvk_number);
        $this->assertTrue($company->users()->where('email', 'tessa@example.test')->exists());
        $this->assertSame(CompanyUserRole::Owner, $company->users()->first()->pivot->role);
        $this->assertSame('Arnhem', $company->primaryLocation->city);
        $this->assertSame('gelderland', $company->primaryLocation->province->slug);
        $this->assertNotNull($company->profile);

        $acceptance = $entry->termsAcceptance;
        $this->assertSame($this->terms->getKey(), $acceptance->terms_version_id);
        $this->assertSame('127.0.0.1', $acceptance->ip);

        $order = $entry->order;
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(74500, $order->subtotal_cents);
        $this->assertSame(15645, $order->vat_cents);
        $this->assertSame(90145, $order->total_cents);
        $this->assertMatchesRegularExpression('/^DGB26-\d{6}$/', $order->number);
        $this->assertStringNotContainsString('plaats', strtolower($order->lines->first()->description));
        $this->assertTrue($order->lines->first()->counts_for_charity);

        $this->assertTrue(AuditLog::query()->where('action', 'entry.registered')->exists());
    }

    #[Test]
    public function an_existing_participant_account_is_reused_by_email(): void
    {
        $existing = ParticipantUser::factory()->create(['email' => 'tessa@example.test']);

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'TESSA@example.test']));

        $this->assertSame(1, ParticipantUser::query()->count());
        $this->assertTrue($entry->company->users()->whereKey($existing->getKey())->exists());
    }

    #[Test]
    public function the_last_place_in_a_province_cannot_be_taken_twice(): void
    {
        $province = Province::query()->where('slug', 'zeeland')->firstOrFail();
        DB::table('edition_province')
            ->where('edition_id', $this->edition->getKey())
            ->where('province_id', $province->getKey())
            ->update(['capacity' => 1]);

        app(RegisterEntry::class)($this->edition, $this->registrationData(['province' => $province, 'email' => 'a@example.test', 'companyName' => 'Bakker A']));

        $this->assertSame(0, app(ProvinceCapacity::class)->available($this->edition, $province));

        $this->expectException(ProvinceFullException::class);

        app(RegisterEntry::class)($this->edition, $this->registrationData(['province' => $province, 'email' => 'b@example.test', 'companyName' => 'Bakker B']));
    }

    #[Test]
    public function an_expired_unpaid_reservation_frees_its_place(): void
    {
        $province = Province::query()->where('slug', 'zeeland')->firstOrFail();
        DB::table('edition_province')
            ->where('edition_id', $this->edition->getKey())
            ->where('province_id', $province->getKey())
            ->update(['capacity' => 1]);

        $first = app(RegisterEntry::class)($this->edition, $this->registrationData(['province' => $province, 'email' => 'a@example.test', 'companyName' => 'Bakker A']));
        $first->forceFill(['reservation_expires_at' => now()->subMinute()])->save();

        $second = app(RegisterEntry::class)($this->edition, $this->registrationData(['province' => $province, 'email' => 'b@example.test', 'companyName' => 'Bakker B']));

        $this->assertInstanceOf(Entry::class, $second);
        $this->assertSame(2, Entry::query()->count());
    }

    #[Test]
    public function the_capacity_overview_counts_only_entries_that_occupy_a_place(): void
    {
        $province = Province::query()->where('slug', 'utrecht')->firstOrFail();

        Entry::factory()->create(['edition_id' => $this->edition->getKey(), 'province_id' => $province->getKey()]);
        Entry::factory()->pendingPayment()->create(['edition_id' => $this->edition->getKey(), 'province_id' => $province->getKey()]);
        Entry::factory()->expiredReservation()->create(['edition_id' => $this->edition->getKey(), 'province_id' => $province->getKey()]);
        Entry::factory()->cancelled()->create(['edition_id' => $this->edition->getKey(), 'province_id' => $province->getKey()]);

        $overview = app(ProvinceCapacity::class)->overview($this->edition);

        $this->assertSame(['capacity' => 50, 'taken' => 2, 'available' => 48], $overview['utrecht']);
    }
}
