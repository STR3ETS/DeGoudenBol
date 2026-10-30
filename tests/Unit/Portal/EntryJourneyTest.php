<?php

namespace Tests\Unit\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Support\Portal\EntryJourney;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntryJourneyTest extends TestCase
{
    #[Test]
    public function without_an_entry_the_next_step_is_to_register(): void
    {
        $journey = new EntryJourney($this->company(), null, null);

        $this->assertSame('Meld', $journey->nextAction()['titel']);
        $this->assertNull($journey->statusLine());
        $this->assertSame(0, $journey->stepsDone());
        $this->assertSame(['later', 'later', 'later', 'later'], array_column($journey->steps(), 'staat'));
    }

    #[Test]
    public function a_registered_entry_asks_to_pick_a_delivery_moment(): void
    {
        $journey = new EntryJourney($this->company(), $this->entry(EntryStatus::Registered), $this->edition());

        $this->assertSame(['klaar', 'nu', 'later', 'later'], array_column($journey->steps(), 'staat'));
        $this->assertSame(1, $journey->stepsDone());
        $this->assertSame('Kies hoe', $journey->nextAction()['knop']);
        $this->assertSame('succes', $journey->statusLine()['toon']);
        $this->assertStringContainsString('1 november en 29 november', $journey->steps()[1]['tekst']);
    }

    #[Test]
    public function a_received_entry_waits_for_the_panel(): void
    {
        $journey = new EntryJourney($this->company(), $this->entry(EntryStatus::Numbered), $this->edition());

        $this->assertSame(['klaar', 'klaar', 'nu', 'later'], array_column($journey->steps(), 'staat'));
        $this->assertSame(2, $journey->stepsDone());
        $this->assertNull($journey->steps()[1]['knop']);
        $this->assertStringContainsString('ontvangen', $journey->nextAction()['accent']);
    }

    #[Test]
    public function a_published_entry_is_completely_done(): void
    {
        $journey = new EntryJourney($this->company(), $this->entry(EntryStatus::Published), $this->edition());

        $this->assertSame(['klaar', 'klaar', 'klaar', 'klaar'], array_column($journey->steps(), 'staat'));
        $this->assertSame(4, $journey->stepsDone());
        $this->assertSame('Bekijk je uitslag', $journey->nextAction()['knop']);
        $this->assertSame('Persoonlijke uitslag en landelijke ranking', $journey->steps()[3]['titel']);
        $this->assertSame('Gefeliciteerd', $journey->nextAction()['eyebrow']);
    }

    #[Test]
    public function an_expired_reservation_is_named_as_such(): void
    {
        $entry = $this->entry(EntryStatus::PendingPayment);
        $entry->reservation_expires_at = CarbonImmutable::now()->subHour();

        $journey = new EntryJourney($this->company(), $entry, $this->edition());

        $this->assertSame('fout', $journey->statusLine()['toon']);
        $this->assertSame('verlopen', $journey->nextAction()['accent']);
        $this->assertSame(['nu', 'later', 'later', 'later'], array_column($journey->steps(), 'staat'));
    }

    #[Test]
    public function a_withdrawn_entry_has_no_steps_left(): void
    {
        $journey = new EntryJourney($this->company(), $this->entry(EntryStatus::Withdrawn), $this->edition());

        $this->assertSame(['later', 'later', 'later', 'later'], array_column($journey->steps(), 'staat'));
        $this->assertNull($journey->nextAction()['knop']);
        $this->assertSame('fout', $journey->statusLine()['toon']);
    }

    private function company(): Company
    {
        $company = new Company(['name' => 'Bakkerij Proef']);
        $company->setRelation('profile', null);

        return $company;
    }

    private function entry(EntryStatus $status): Entry
    {
        $entry = new Entry(['public_name' => 'Bakkerij Proef, Arnhem', 'status' => $status]);
        $entry->setRelation('province', new Province(['name' => 'Gelderland']));
        $entry->setRelation('order', null);
        $entry->setRelation('deliverySlot', null);
        $entry->setRelation('publicationItems', collect());

        return $entry;
    }

    private function edition(): Edition
    {
        return new Edition([
            'year' => 2026,
            'first_test_day' => '2026-11-01',
            'last_test_day' => '2026-11-29',
            'main_publication_at' => '2026-12-21 11:00:00',
        ]);
    }
}
