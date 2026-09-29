<?php

namespace Database\Factories;

use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'story' => fake()->paragraphs(2, true),
            'tagline' => fake()->sentence(6),
            'specialties' => ['Oliebollen met krenten en rozijnen'],
        ];
    }

    public function approved(): static
    {
        return $this->afterCreating(function (Profile $profile): void {
            $profile->forceFill([
                'moderation_status' => ModerationStatus::Approved,
                'published' => $profile->only(Profile::PUBLISHED_FIELDS),
                'reviewed_at' => now(),
            ])->save();
        });
    }

    public function pending(): static
    {
        return $this->afterCreating(fn (Profile $profile) => $profile->submitForReview());
    }
}
