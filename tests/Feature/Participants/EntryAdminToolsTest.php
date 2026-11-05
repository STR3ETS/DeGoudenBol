<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Models\Order;
use App\Domain\Participants\Actions\DeleteUnpaidEntry;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Resources\Entries\EntryExport;
use App\Filament\Resources\Entries\EntryResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Backoffice-wensen van Gill en Martijn (4 nov 2026): testaanmeldingen verwijderen, adressen en e-mail zien
 * en exporteren, en alleen betaalde inschrijvingen tonen.
 */
class EntryAdminToolsTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->admin = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->admin->assignRole(StaffRole::Admin->value);
    }

    #[Test]
    public function an_unpaid_test_registration_is_deleted_with_its_company_and_account(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => 'Test', 'publicName' => 'Test', 'email' => 'test@example.test']));

        app(DeleteUnpaidEntry::class)($entry, $this->admin);

        $this->assertNull(Entry::query()->find($entry->getKey()));
        $this->assertNull(Company::query()->find($entry->company_id));
        $this->assertSame(0, Order::query()->count());
        $this->assertFalse(ParticipantUser::query()->where('email', 'test@example.test')->exists());
    }

    #[Test]
    public function a_paid_registration_can_never_be_deleted(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        app(MarkOrderPaid::class)($entry->order);

        $this->assertFalse(DeleteUnpaidEntry::canDelete($entry->fresh()));
        $this->expectException(InvalidArgumentException::class);

        app(DeleteUnpaidEntry::class)($entry->fresh(), $this->admin);
    }

    #[Test]
    public function the_export_lists_contact_email_and_address(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        app(MarkOrderPaid::class)($entry->order);

        $response = EntryExport::download(Entry::query());

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('e-mail;telefoon;adres;postcode;plaats', $csv);
        $this->assertStringContainsString('tessa@example.test', $csv);
        $this->assertStringContainsString('Bakkersstraat 12a', $csv);
        $this->assertStringContainsString('Arnhem', $csv);
    }

    #[Test]
    public function the_backoffice_shows_the_paid_tab_and_contact_details(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        app(MarkOrderPaid::class)($entry->order);

        $this->actingAs($this->admin)
            ->get(EntryResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Betaald')
            ->assertSee('Exporteren (Excel/CSV)');
    }
}
