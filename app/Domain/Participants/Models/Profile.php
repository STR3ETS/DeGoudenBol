<?php

namespace App\Domain\Participants\Models;

use App\Domain\Participants\Enums\ModerationStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Openbare tekst van een bedrijf. Wijzigingen gaan langs Communicatie; de
 * publiekssite toont altijd de laatst goedgekeurde versie ('published').
 */
#[Fillable(['company_id', 'story', 'tagline', 'specialties'])]
class Profile extends DomainModel
{
    use HasFactory;

    public const array PUBLISHED_FIELDS = ['story', 'tagline', 'specialties'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'moderation_status' => ModerationStatus::class,
            'published' => 'array',
            'submitted_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitForReview(): void
    {
        $this->forceFill([
            'moderation_status' => ModerationStatus::Pending,
            'submitted_at' => now(),
            'review_note' => null,
        ])->save();
    }

    public function approve(User $reviewer): void
    {
        $this->forceFill([
            'moderation_status' => ModerationStatus::Approved,
            'published' => $this->only(self::PUBLISHED_FIELDS),
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => null,
        ])->save();
    }

    public function reject(User $reviewer, string $note): void
    {
        $this->forceFill([
            'moderation_status' => ModerationStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();
    }

    public function isPublished(): bool
    {
        return filled($this->published);
    }

    public function publishedStory(): ?string
    {
        return $this->published['story'] ?? null;
    }

    public function publishedTagline(): ?string
    {
        return $this->published['tagline'] ?? null;
    }

    /**
     * @return list<string>
     */
    public function publishedSpecialties(): array
    {
        return $this->published['specialties'] ?? [];
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->only(self::PUBLISHED_FIELDS) !== ($this->published ?? []);
    }
}
