<?php

namespace Database\Factories;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermsVersion>
 */
class TermsVersionFactory extends Factory
{
    protected $model = TermsVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => null,
            'type' => TermsType::Participation,
            'version' => fake()->unique()->numerify('2026.#'),
            'title' => 'Deelnamevoorwaarden',
            'body' => fake()->paragraphs(3, true),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()->subDay()]);
    }

    public function ofType(TermsType $type): static
    {
        return $this->state(fn () => ['type' => $type, 'title' => $type->getLabel()]);
    }
}
