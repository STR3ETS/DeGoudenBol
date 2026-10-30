<?php

namespace App\Support\Mail;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Mail in de huisstijl van het template "Emailtemplate Uitslag": titel in de donkere kop, een
 * crèmekleurige kaart met de kerngegevens, een gouden knop en een beige blok onderaan.
 * De opmaak staat in resources/views/vendor/notifications/email.blade.php.
 */
class GoldenMail extends MailMessage
{
    /**
     * Titel in de donkere kop; de tweede regel staat cursief in goud.
     */
    public function hero(string $title, ?string $accent = null): static
    {
        $this->viewData['heroTitle'] = $title;
        $this->viewData['heroAccent'] = $accent;

        return $this;
    }

    /**
     * Voorvertoningstekst in de inbox.
     */
    public function preheader(string $text): static
    {
        $this->viewData['preheader'] = $text;

        return $this;
    }

    /**
     * Toont het adres van de ontvanger in de voettekst.
     */
    public function recipient(?string $email): static
    {
        $this->viewData['recipient'] = $email;

        return $this;
    }

    /**
     * Crèmekleurige kaart met een kleine kop, een titel en losse regels.
     *
     * @param  list<string>  $lines
     */
    public function panel(?string $eyebrow, ?string $title, array $lines, ?string $footnote = null): static
    {
        $this->viewData['panel'] = [
            'eyebrow' => $eyebrow,
            'title' => $title,
            'lines' => array_values(array_filter($lines, fn (?string $line) => filled($line))),
            'footnote' => $footnote,
        ];

        return $this;
    }

    /**
     * Beige blok onder de knop, voor de vervolgstap of een geruststelling.
     */
    public function note(string $title, string $text): static
    {
        $this->viewData['note'] = ['title' => $title, 'text' => $text];

        return $this;
    }
}
