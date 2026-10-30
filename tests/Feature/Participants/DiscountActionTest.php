<?php

namespace Tests\Feature\Participants;

use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Actions\ReviewDiscount;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\DiscountRequestedNotification;
use App\Domain\Participants\Notifications\DiscountReviewedNotification;
use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Kortingsactie uit de briefing van 29 okt (§3): actieprijs pas na goedkeuring van het bewijs.
 */
class DiscountActionTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole(StaffRole::Finance->value);
    }

    #[Test]
    public function a_discount_registration_holds_the_place_and_cannot_be_paid_until_the_proof_is_approved(): void
    {
        Notification::fake();

        $entry = $this->registerWithDiscount();
        $owner = $this->ownerOf($entry);

        $this->assertSame(EntryRate::Discount, $entry->rate);
        $this->assertSame(DiscountStatus::AwaitingProof, $entry->discount_status);
        $this->assertTrue($entry->reservation_expires_at->isAfter(now()->addDays(6)));
        $this->assertFalse($entry->canPay());
        Notification::assertSentTo($owner, DiscountRequestedNotification::class);

        $this->get(route('aanmelden.status', $entry->order))->assertOk()->assertSee('Bedankt, uw aanmelding is')->assertSee('Toch direct betalen');
        $this->post(route('aanmelden.opnieuw', $entry->order))->assertRedirect(route('aanmelden.status', $entry->order));
        $this->assertSame(0, $entry->order->payments()->count());

        $this->actingAs($owner, 'participant')
            ->post(route('portaal.aanmelding.korting', $entry->company), ['link' => 'https://www.instagram.com/p/oliebol'])
            ->assertRedirect(route('portaal.aanmelding', $entry->company));
        $this->assertSame(DiscountStatus::Submitted, $entry->fresh()->discount_status);

        app(ReviewDiscount::class)($entry->fresh(), true, $this->reviewer);

        $entry->refresh();
        $this->assertSame(DiscountStatus::Approved, $entry->discount_status);
        $this->assertSame(9075, $entry->order->total_cents); // EUR 75 + 21% btw
        $this->assertTrue($entry->canPay());
        Notification::assertSentTo($owner, DiscountReviewedNotification::class, fn (DiscountReviewedNotification $mail) => $mail->approved);

        app(MarkOrderPaid::class)($entry->order);

        $entry->refresh();
        $this->assertSame(EntryStatus::Registered, $entry->status);
        $this->assertSame(2, $entry->order->lines()->count());
        $this->assertSame(9075, $entry->order->invoice->total_cents);

        // 10% voor goede doelen over wat er werkelijk betaald is: EUR 75 excl. btw.
        $reservations = CharityReservation::query()->whereIn('order_line_id', $entry->order->lines()->pluck('id'))->get();
        $this->assertSame(1, $reservations->count());
        $this->assertSame(7500, $reservations->first()->basis_cents);
        $this->assertSame(750, $reservations->first()->amount_cents);
    }

    #[Test]
    public function a_rejected_proof_comes_with_a_reason_and_the_baker_may_retry_or_pay_the_standard_price(): void
    {
        Notification::fake();

        $entry = $this->registerWithDiscount('https://www.facebook.com/post/1');
        $this->assertSame(DiscountStatus::Submitted, $entry->discount_status);

        try {
            app(ReviewDiscount::class)($entry, false, $this->reviewer);
            $this->fail('Afwijzen zonder reden hoort niet te kunnen.');
        } catch (InvalidArgumentException) {
        }

        app(ReviewDiscount::class)($entry, false, $this->reviewer, 'De Gouden Bol is niet getagd.');

        $entry->refresh();
        $this->assertSame(DiscountStatus::Rejected, $entry->discount_status);
        $this->assertSame(18150, $entry->order->total_cents);
        $this->assertTrue($entry->canPay());
        $this->assertTrue($entry->canSubmitDiscountProof());
        Notification::assertSentTo($this->ownerOf($entry), DiscountReviewedNotification::class, fn (DiscountReviewedNotification $mail) => ! $mail->approved);
    }

    #[Test]
    public function choosing_the_standard_price_skips_the_review_and_goes_to_the_checkout(): void
    {
        Notification::fake();

        $entry = $this->registerWithDiscount();

        $this->post(route('aanmelden.standaardprijs', $entry->order))->assertRedirect();

        $entry->refresh();
        $this->assertSame(EntryRate::Standard, $entry->rate);
        $this->assertSame(18150, $entry->order->total_cents);
        $this->assertSame(1, $entry->order->payments()->count());
    }

    private function registerWithDiscount(?string $proof = null): Entry
    {
        return app(RegisterEntry::class)($this->edition, $this->registrationData(['rate' => EntryRate::Discount, 'discountProofUrl' => $proof]))->fresh();
    }

    private function ownerOf(Entry $entry): ParticipantUser
    {
        return $entry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();
    }
}
