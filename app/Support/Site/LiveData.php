<?php

namespace App\Support\Site;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Models\RankingPosition;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Support\DutchTime;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;

/**
 * De "live"-gegevens van de publiekssite: nieuwe deelnemers en de Voorlijst per provincie.
 * Gelezen door de zwevende widget op iedere pagina (ingebed als #gb-live en vers via /live.json).
 * Het formaat is dat van de site-export van Eazyonline; verander de sleutels niet zonder het script aan te passen.
 *
 * @phpstan-type Live array{updated: string, start: string, capacity: int, edition: string, provinces: list<array{slug: string, name: string}>, signups: list<array{name: string, city: string, province: string, date: string, url: string}>, total: int, rankings: array<string, list<array{name: string, city: string, status: string, score: float, url: string}>>}
 */
final class LiveData
{
    public const int TTL_SECONDS = 120;

    /**
     * @return Live
     */
    public static function get(): array
    {
        return Cache::remember('site.live.'.PublicCache::generation(), self::TTL_SECONDS, fn () => self::build());
    }

    public static function json(): string
    {
        return json_encode(self::get(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return Live
     */
    public static function build(): array
    {
        $edition = Edition::query()->current()->with('provinces')->first();
        $provinces = $edition?->provinces->sortBy('sort')->values() ?? Province::query()->orderBy('sort')->get();

        $signups = $edition === null ? collect() : Entry::query()
            ->where('edition_id', $edition->getKey())
            ->confirmed()
            ->with(['province', 'company.primaryLocation'])
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $rankings = [];

        foreach ($provinces as $province) {
            $snapshot = $edition === null ? null : RankingSnapshot::latestForProvince($edition->getKey(), $province->getKey());
            $positions = $snapshot === null ? collect() : $snapshot->positions()->with(['entry.company.primaryLocation'])->orderBy('position')->get()
                ->filter(fn (RankingPosition $position) => $position->entry?->status->isPubliclyVisible());

            $rankings[$province->slug] = $positions->map(fn (RankingPosition $position) => [
                'name' => $position->entry->public_name,
                'city' => (string) $position->entry->company?->primaryLocation?->city,
                'status' => $position->label->getLabel(),
                'score' => round((float) $position->total, 1),
                'url' => route('bakkers.toon', $position->entry->company),
            ])->values()->all();
        }

        return [
            'updated' => now()->toIso8601String(),
            'start' => $edition?->first_test_day?->format('Y-m-d') ?? '',
            'capacity' => (int) $provinces->sum(fn (Province $province) => (int) ($province->pivot->capacity ?? 0)),
            'edition' => (string) ($edition?->year ?? now()->year),
            'provinces' => $provinces->map(fn (Province $province) => ['slug' => $province->slug, 'name' => $province->name])->values()->all(),
            'signups' => $signups->map(fn (Entry $entry) => [
                'name' => $entry->public_name,
                'city' => (string) $entry->company?->primaryLocation?->city,
                'province' => $entry->province?->name ?? '',
                'date' => ($entry->confirmed_at ?? $entry->registered_at ?? $entry->created_at)?->setTimezone(DutchTime::zone())->format('Y-m-d') ?? '',
                'url' => route('bakkers.toon', $entry->company),
            ])->values()->all(),
            'total' => $edition === null ? 0 : Entry::query()->where('edition_id', $edition->getKey())->confirmed()->count(),
            'rankings' => $rankings,
        ];
    }
}
