<?php

namespace Tests\Feature;

use App\Domain\Platform\Models\NewsletterSubscription;
use App\Domain\Platform\Notifications\NewsletterConfirmNotification;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProvinceSeeder::class, Edition2026Seeder::class]);
    }

    #[Test]
    public function subscribing_requires_a_confirmation_click_and_unsubscribing_works_via_a_signed_link(): void
    {
        Notification::fake();

        $this->get('/')->assertOk()->assertSee(route('nieuwsbrief.aanmelden'));

        $this->from('/')->post(route('nieuwsbrief.aanmelden'), ['email' => 'geen-adres'])->assertSessionHasErrors('email');
        $this->from('/')->post(route('nieuwsbrief.aanmelden'), ['email' => 'Lezer@Example.test'])->assertRedirectContains('#nieuwsbrief')->assertSessionHas('nieuwsbrief');

        $subscription = NewsletterSubscription::query()->where('email', 'lezer@example.test')->firstOrFail();
        $this->assertFalse($subscription->isActive());

        $confirmUrl = null;
        Notification::assertSentOnDemand(NewsletterConfirmNotification::class, function (NewsletterConfirmNotification $notification) use (&$confirmUrl): bool {
            $confirmUrl = $notification->url;

            return true;
        });

        $this->get(route('nieuwsbrief.bevestigen', $subscription))->assertForbidden();
        $this->get($confirmUrl)->assertOk()->assertSee('Inschrijving bevestigd');
        $this->assertTrue($subscription->fresh()->isActive());

        // Nogmaals aanmelden terwijl actief → geen tweede mail.
        $this->post(route('nieuwsbrief.aanmelden'), ['email' => 'lezer@example.test']);
        Notification::assertSentOnDemandTimes(NewsletterConfirmNotification::class, 1);

        $this->get(URL::signedRoute('nieuwsbrief.afmelden', ['subscription' => $subscription]))->assertOk()->assertSee('Afgemeld');
        $this->assertFalse($subscription->fresh()->isActive());

        // Na afmelden opnieuw aanmelden → nieuwe bevestiging.
        $this->post(route('nieuwsbrief.aanmelden'), ['email' => 'lezer@example.test']);
        Notification::assertSentOnDemandTimes(NewsletterConfirmNotification::class, 2);
    }
}
