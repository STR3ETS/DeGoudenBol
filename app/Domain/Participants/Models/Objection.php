<?php

namespace App\Domain\Participants\Models;

use App\Domain\Participants\Enums\ObjectionStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bezwaar van een deelnemer, alleen over de procedure (besluit 7): binnen drie werkdagen na publicatie.
 *
 * @property ObjectionStatus $status
 */
#[Fillable(['entry_id', 'participant_user_id', 'reason', 'status', 'decision', 'decided_by', 'submitted_at', 'decided_at'])]
class Objection extends DomainModel
{
    public const int WINDOW_WORKING_DAYS = 3;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ObjectionStatus::class,
            'submitted_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function participantUser(): BelongsTo
    {
        return $this->belongsTo(ParticipantUser::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
