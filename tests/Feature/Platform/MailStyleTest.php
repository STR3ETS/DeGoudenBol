<?php

namespace Tests\Feature\Platform;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\MagicLinkNotification;
use App\Support\Mail\GoldenMail;
use App\Support\Mail\SendOnce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Alle mails in de huisstijl van het aangeleverde template, en nooit twee keer dezelfde statusmail.
 */
class MailStyleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_mail_uses_the_house_style_with_hero_card_button_and_note(): void
    {
        $html = (string) (new GoldenMail)
            ->subject('Test')
            ->hero('Uw aanmelding is', 'binnen.')
            ->greeting('Beste Tessa,')
            ->line('Een regel tekst.')
            ->panel('Uw aanmelding', 'Bakkerij Test', ['Deelnemercode: ABCD-1234'])
            ->action('Naar het portaal', 'https://example.test/portaal')
            ->note('Goed om te weten', 'Een geruststelling.')
            ->recipient('tessa@example.test')
            ->render();

        foreach (['De beste oliebol verdient een podium', '#1e1207', 'Uw aanmelding is', 'binnen.', 'Deelnemercode: ABCD-1234', '#cf8c08', 'Goed om te weten', 'Team De Gouden Bol', 'Nederlands Test Instituut', 'tessa@example.test'] as $needle) {
            $this->assertStringContainsString($needle, $html, "Ontbreekt in de mail: {$needle}");
        }
    }

    #[Test]
    public function existing_notifications_render_in_the_new_layout(): void
    {
        $user = ParticipantUser::factory()->create(['name' => 'Tessa Tester']);

        $html = (string) (new MagicLinkNotification('https://example.test/inloggen'))->toMail($user)->render();

        $this->assertStringContainsString('#1e1207', $html);
        $this->assertStringContainsString('Beste Tessa Tester', $html);
    }

    #[Test]
    public function send_once_never_sends_the_same_status_mail_twice(): void
    {
        Notification::fake();
        $user = ParticipantUser::factory()->create();
        $mail = app(SendOnce::class);
        $statusMail = new StatusMailStub;

        $this->assertTrue($mail->send($user, $statusMail, 'order:1:paid'));
        $this->assertFalse($mail->send($user, $statusMail, 'order:1:paid'));
        $this->assertTrue($mail->send($user, $statusMail, 'order:2:paid'));
        $this->assertTrue($mail->send('Iemand@Example.test', $statusMail, 'order:1:paid'));
        $this->assertFalse($mail->send('iemand@example.test', $statusMail, 'order:1:paid'));

        Notification::assertSentToTimes($user, StatusMailStub::class, 2);
        Notification::assertSentOnDemandTimes(StatusMailStub::class, 1);
    }
}

/**
 * Een kale statusmail voor de test van SendOnce.
 */
class StatusMailStub extends BaseNotification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
