<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class CancelExpiredReservationsTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function expired_unpaid_entries_are_cancelled_and_their_orders_expired(): void
    {
        $expired = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'a@example.test', 'companyName' => 'Bakker A']));
        $expired->forceFill(['reservation_expires_at' => now()->subMinute()])->save();

        $fresh = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Bakker B']));

        $this->artisan('entries:cancel-expired')->assertSuccessful();

        $this->assertSame(EntryStatus::Cancelled, $expired->refresh()->status);
        $this->assertSame(OrderStatus::Expired, $expired->order->refresh()->status);
        $this->assertSame(EntryStatus::PendingPayment, $fresh->refresh()->status);
        $this->assertSame(OrderStatus::Pending, $fresh->order->refresh()->status);
    }
}
