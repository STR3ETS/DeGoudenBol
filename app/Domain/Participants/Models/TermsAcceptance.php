<?php

namespace App\Domain\Participants\Models;

use App\Domain\Edition\Models\TermsVersion;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bewijs van akkoord op een voorwaardenversie: wie, voor welk bedrijf, wanneer, vanaf welk adres.
 */
#[Fillable(['participant_user_id', 'company_id', 'terms_version_id', 'accepted_at', 'ip', 'user_agent'])]
class TermsAcceptance extends DomainModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ParticipantUser::class, 'participant_user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function termsVersion(): BelongsTo
    {
        return $this->belongsTo(TermsVersion::class);
    }
}
