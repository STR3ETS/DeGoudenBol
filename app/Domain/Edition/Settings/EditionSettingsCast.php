<?php

namespace App\Domain\Edition\Settings;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<EditionSettings, EditionSettings|array<string, mixed>>
 */
final class EditionSettingsCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): EditionSettings
    {
        $data = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : ($value ?? []);

        return EditionSettings::fromArray(is_array($data) ? $data : []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $settings = match (true) {
            $value instanceof EditionSettings => $value,
            is_array($value) => EditionSettings::fromArray($value),
            $value === null => new EditionSettings,
            default => throw new InvalidArgumentException('Editie-instellingen moeten een EditionSettings-object of array zijn.'),
        };

        return json_encode($settings->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): array
    {
        return $value instanceof EditionSettings ? $value->toArray() : (array) $value;
    }
}
