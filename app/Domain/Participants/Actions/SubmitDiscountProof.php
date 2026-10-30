<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Events\DiscountProofSubmitted;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Kortingsactie: de bakker levert de link naar zijn post en eventueel een schermafbeelding aan.
 * De korting geldt pas nadat een administrator het bewijs heeft goedgekeurd.
 */
final class SubmitDiscountProof
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Entry $entry, ?string $url, ?UploadedFile $file = null): Entry
    {
        if (! $entry->canSubmitDiscountProof()) {
            throw new InvalidArgumentException('Voor deze aanmelding kan geen bewijs (meer) worden aangeleverd.');
        }

        if (blank($url) && $file === null) {
            throw new InvalidArgumentException('Vul de link naar uw post in of voeg een schermafbeelding toe.');
        }

        $path = $file?->store('discount-proofs', 'local');

        $entry->forceFill([
            'discount_status' => DiscountStatus::Submitted,
            'discount_proof_url' => filled($url) ? trim((string) $url) : $entry->discount_proof_url,
            'discount_proof_path' => $path ?? $entry->discount_proof_path,
            'discount_submitted_at' => now(),
            'discount_note' => null,
        ])->save();

        $this->audit->record('entry.discount_submitted', $entry, ['url' => $entry->discount_proof_url, 'file' => $path !== null], null);

        DiscountProofSubmitted::dispatch($entry);

        return $entry;
    }
}
