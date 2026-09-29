<?php

namespace Tests\Unit;

use App\Support\DesignTokens;
use Filament\Support\Colors\Color;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    #[Test]
    public function it_exposes_the_brand_colours(): void
    {
        $tokens = new DesignTokens;

        $this->assertSame('#C8860A', $tokens->color('goud'));
        $this->assertSame('#8A5A00', $tokens->color('goud-tekst'));
        $this->assertSame('#2F6B3A', $tokens->color('status.succes.text'));
        $this->assertCount(11, $tokens->grayPalette());
        $this->assertArrayHasKey('kop', $tokens->fonts());
        $this->assertArrayHasKey('brood', $tokens->fonts());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function textOnBackgroundPairs(): array
    {
        return [
            'goud-tekst op room' => ['goud-tekst', 'room'],
            'goud-tekst op zand' => ['goud-tekst', 'zand'],
            'goud-tekst op wit' => ['goud-tekst', 'wit'],
            'gedempt op zand' => ['gedempt', 'zand'],
            'gedempt op room' => ['gedempt', 'room'],
            'espresso op room' => ['espresso', 'room'],
            'espresso op goud (knoptekst)' => ['espresso', 'goud'],
            'goud op espresso' => ['goud', 'espresso'],
        ];
    }

    #[Test]
    #[DataProvider('textOnBackgroundPairs')]
    public function text_colours_meet_wcag_aa_on_their_backgrounds(string $text, string $background): void
    {
        $tokens = new DesignTokens;

        $ratio = Color::calculateContrastRatio($tokens->color($text), $tokens->color($background));

        $this->assertGreaterThanOrEqual(4.5, $ratio, "{$text} op {$background} haalt {$ratio}:1");
    }

    #[Test]
    public function status_colours_meet_wcag_aa_on_their_own_backgrounds(): void
    {
        $tokens = new DesignTokens;

        foreach ($tokens->statusColors() as $name => $definition) {
            $ratio = Color::calculateContrastRatio($definition['text'], $definition['bg']);

            $this->assertGreaterThanOrEqual(4.5, $ratio, "status {$name} haalt {$ratio}:1");
        }
    }

    #[Test]
    public function the_generated_css_files_are_up_to_date(): void
    {
        $this->artisan('design:tokens', ['--check' => true])->assertSuccessful();

        $css = file_get_contents(resource_path('css/tokens.css'));

        $this->assertStringContainsString('--color-goud: #C8860A;', $css);
        $this->assertStringContainsString('--font-kop:', $css);
        $this->assertStringContainsString('--radius-kaart: 24px;', $css);
    }
}
