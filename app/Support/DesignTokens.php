<?php

namespace App\Support;

use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Leest resources/design/tokens.json: de enige bron voor kleuren, lettertypen,
 * radius, schaduw en layout van publiekssite, portaal, backoffice, badges en e-mail.
 */
final class DesignTokens
{
    /** @var array<string, mixed> */
    private readonly array $data;

    public function __construct(?string $path = null)
    {
        $path ??= resource_path('design/tokens.json');

        if (! is_file($path)) {
            throw new RuntimeException("Tokenbestand niet gevonden: {$path}");
        }

        $this->data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Eén kleurwaarde, bijvoorbeeld 'goud', 'goud-tekst' of 'status.succes.text'.
     */
    public function color(string $name): string
    {
        $value = Arr::get($this->data, "color.{$name}");

        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        if (! is_string($value)) {
            throw new RuntimeException("Onbekende kleurtoken: {$name}");
        }

        return $value;
    }

    /**
     * Alle losse kleuren als naam => waarde (zonder status en backoffice-grijs).
     *
     * @return array<string, string>
     */
    public function colors(): array
    {
        $colors = [];

        foreach ($this->data['color'] as $name => $definition) {
            if (is_array($definition) && array_key_exists('value', $definition)) {
                $colors[$name] = $definition['value'];
            }
        }

        return $colors;
    }

    /**
     * @return array<string, array{text: string, bg: string, contrast?: string}>
     */
    public function statusColors(): array
    {
        return $this->data['color']['status'];
    }

    /**
     * Warme grijsschaal voor de backoffice, shade => hex.
     *
     * @return array<int, string>
     */
    public function grayPalette(): array
    {
        $palette = [];

        foreach ($this->data['color']['backoffice-grijs'] as $shade => $hex) {
            $palette[(int) $shade] = $hex;
        }

        return $palette;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fonts(): array
    {
        return Arr::except($this->data['font'], ['mail']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function typeScale(): array
    {
        return $this->data['type'];
    }

    /**
     * @return array<string, string>
     */
    public function radii(): array
    {
        return $this->data['radius'];
    }

    /**
     * @return array<string, string>
     */
    public function shadows(): array
    {
        return $this->data['shadow'];
    }

    /**
     * @return array<string, mixed>
     */
    public function layout(): array
    {
        return $this->data['layout'];
    }

    /**
     * @return array<string, mixed>
     */
    public function motion(): array
    {
        return $this->data['motion'];
    }

    public function logoSvg(bool $dark = false): string
    {
        $svg = $this->data['logo']['svg'];

        return $dark
            ? str_replace($this->color('room'), $this->color('espresso'), $svg)
            : $svg;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
