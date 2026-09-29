<?php

namespace Tests\Feature\Participants;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Livewire\Public\RegistrationWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class RegistrationWizardTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();

        Http::fake([
            'api.pdok.nl/*' => Http::response([
                'response' => ['docs' => [[
                    'straatnaam' => 'Bakkersstraat',
                    'woonplaatsnaam' => 'Arnhem',
                    'provincienaam' => 'Gelderland',
                    'postcode' => '6811AB',
                    'huisnummer' => 12,
                    'centroide_ll' => 'POINT(5.91 51.98)',
                ]]],
            ]),
        ]);
    }

    #[Test]
    public function the_page_shows_a_closed_message_when_registration_is_not_open(): void
    {
        $this->edition->forceFill(['status' => EditionStatus::Draft])->save();

        $this->get(route('aanmelden'))
            ->assertOk()
            ->assertSee('nog niet open')
            ->assertSee('12 oktober 2026');
    }

    #[Test]
    public function step_one_validates_the_company_details_in_dutch(): void
    {
        Livewire::test(RegistrationWizard::class)
            ->set('companyName', 'B')
            ->set('email', 'geen-email')
            ->call('next')
            ->assertHasErrors(['companyName', 'companyType', 'contactName', 'email'])
            ->assertSet('step', 1)
            ->assertSee('e-mailadres');
    }

    #[Test]
    public function the_address_is_completed_via_pdok(): void
    {
        $gelderland = Province::query()->where('slug', 'gelderland')->firstOrFail();

        Livewire::test(RegistrationWizard::class)
            ->set('step', 2)
            ->set('postcode', '6811ab')
            ->set('houseNumber', '12')
            ->assertSet('street', 'Bakkersstraat')
            ->assertSet('city', 'Arnhem')
            ->assertSet('postcode', '6811 AB')
            ->assertSet('provinceId', $gelderland->getKey())
            ->assertSet('lat', 51.98)
            ->assertSee('Nog 50 van 50 plekken');
    }

    #[Test]
    public function a_full_province_blocks_the_second_step(): void
    {
        $zeeland = Province::query()->where('slug', 'zeeland')->firstOrFail();
        DB::table('edition_province')->where('edition_id', $this->edition->getKey())->where('province_id', $zeeland->getKey())->update(['capacity' => 0]);

        Livewire::test(RegistrationWizard::class)
            ->set('step', 2)
            ->set('postcode', '4331 AA')
            ->set('houseNumber', '1')
            ->set('street', 'Markt')
            ->set('city', 'Middelburg')
            ->set('provinceId', $zeeland->getKey())
            ->call('next')
            ->assertHasErrors(['provinceId'])
            ->assertSet('step', 2);
    }

    #[Test]
    public function a_kvk_number_that_already_has_an_entry_is_rejected(): void
    {
        $company = Company::factory()->create(['kvk_number' => '87654321']);
        Entry::factory()->create(['edition_id' => $this->edition->getKey(), 'company_id' => $company->getKey()]);

        $this->completeStepOne(Livewire::test(RegistrationWizard::class), kvk: '87654321')
            ->call('next')
            ->assertHasErrors(['kvkNumber'])
            ->assertSet('step', 1);
    }

    #[Test]
    public function completing_all_steps_registers_the_entry_and_redirects_to_the_checkout(): void
    {
        $component = $this->completeStepOne(Livewire::test(RegistrationWizard::class))
            ->call('next')
            ->assertSet('step', 2)
            ->assertSet('publicName', 'Bakkerij Testers')
            ->set('postcode', '6811 AB')
            ->set('houseNumber', '12')
            ->call('next')
            ->assertSet('step', 3)
            ->set('tagline', 'Sinds kort de lekkerste van de straat')
            ->set('allergens', ['gluten', 'melk'])
            ->call('next')
            ->assertSet('step', 4)
            ->assertSet('packageId', $this->package->getKey())
            ->call('next')
            ->assertSet('step', 5)
            ->assertSee('Bakkerij Testers')
            ->assertSee('€ 901,45')
            ->call('submit')
            ->assertHasErrors(['acceptTerms', 'acceptPrivacy'])
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->call('submit')
            ->assertHasNoErrors();

        $entry = Entry::query()->firstOrFail();

        $this->assertSame(EntryStatus::PendingPayment, $entry->status);
        $this->assertSame('gelderland', $entry->province->slug);
        $this->assertSame(['gluten', 'melk'], $entry->allergens);
        $this->assertSame(51.98, $entry->company->primaryLocation->lat);

        $payment = $entry->order->latestPayment;
        $this->assertNotNull($payment);
        $component->assertRedirect(route('betaling.fake', $payment));
    }

    private function completeStepOne(Testable $component, string $kvk = '12345678'): Testable
    {
        return $component
            ->set('companyName', 'Bakkerij Testers')
            ->set('companyType', 'bakkerij')
            ->set('kvkNumber', $kvk)
            ->set('contactName', 'Tessa Tester')
            ->set('email', 'tessa@example.test')
            ->set('phone', '0612345678');
    }
}
