<?php

namespace App\Domain\Charities\Actions;

use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Voordracht door een deelnemer (bedrijf) of sponsor: één per voordrager per editie.
 */
final class NominateCharity
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, kvk_or_rsin?: string|null, is_anbi?: bool, province_id?: int|null, category?: string|null, motivation?: string|null, website?: string|null}  $data
     */
    public function __invoke(Model $nominator, Edition $edition, array $data): Charity
    {
        $exists = Charity::query()
            ->where('edition_id', $edition->getKey())
            ->where('nominated_by_type', $nominator->getMorphClass())
            ->where('nominated_by_id', $nominator->getKey())
            ->exists();

        if ($exists) {
            throw new CharityException('Er is voor deze editie al een goed doel voorgedragen.');
        }

        $charity = Charity::query()->create([
            'edition_id' => $edition->getKey(),
            'name' => trim($data['name']),
            'kvk_or_rsin' => $data['kvk_or_rsin'] ?? null,
            'is_anbi' => (bool) ($data['is_anbi'] ?? false),
            'province_id' => $data['province_id'] ?? null,
            'category' => $data['category'] ?? null,
            'motivation' => $data['motivation'] ?? null,
            'website' => $data['website'] ?? null,
            'status' => CharityStatus::Nominated,
            'nominated_by_type' => $nominator->getMorphClass(),
            'nominated_by_id' => $nominator->getKey(),
        ]);

        $this->audit->record('charity.nominated', $charity, ['by' => $nominator->getMorphClass().'#'.$nominator->getKey()], null);

        return $charity;
    }
}
