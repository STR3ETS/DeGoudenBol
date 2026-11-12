<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\TermsAcceptance;
use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Legt het akkoord op de deelnamevoorwaarden vast voor een aanmelding die een medewerker heeft klaargezet.
 * De klant geeft dat akkoord zelf op de betaalpagina; zonder akkoord geen betaling.
 */
final class AcceptTermsForEntry
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Entry $entry, ?string $ip = null, ?string $userAgent = null): TermsAcceptance
    {
        return DB::transaction(function () use ($entry, $ip, $userAgent): TermsAcceptance {
            $entry = Entry::query()->lockForUpdate()->findOrFail($entry->getKey());

            if ($entry->termsAcceptance !== null) {
                return $entry->termsAcceptance;
            }

            $terms = TermsVersion::latestPublished(TermsType::Participation)
                ?? throw new RuntimeException('Er zijn geen gepubliceerde deelnamevoorwaarden.');
            $owner = $entry->company->owners()->first()
                ?? throw new RuntimeException('Bij deze aanmelding hoort geen account.');

            $acceptance = TermsAcceptance::query()->create([
                'participant_user_id' => $owner->getKey(),
                'company_id' => $entry->company_id,
                'terms_version_id' => $terms->getKey(),
                'accepted_at' => now(),
                'ip' => $ip,
                'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
            ]);

            $entry->forceFill(['terms_acceptance_id' => $acceptance->getKey()])->save();

            $this->audit->record('entry.terms_accepted', $entry, ['terms_version' => $terms->version], $owner);

            return $acceptance;
        });
    }
}
