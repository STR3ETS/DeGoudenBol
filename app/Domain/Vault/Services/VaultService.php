<?php

namespace App\Domain\Vault\Services;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vault\Exceptions\VaultAccessDeniedException;
use App\Domain\Vault\Models\VaultAccessLog;
use App\Domain\Vault\Models\VaultLink;
use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Enige toegang tot de koppeling testnummer <-> inschrijving. Iedere aanroep wordt gelogd;
 * alleen Ontvangst & registratie, Publicatie en systeemprocessen mogen erbij.
 */
final class VaultService
{
    private Encrypter $encrypter;

    private string $key;

    private bool $systemContext = false;

    public function __construct()
    {
        $configured = (string) config('vault.key');

        if ($configured === '') {
            throw new RuntimeException('VAULT_ENCRYPTION_KEY ontbreekt.');
        }

        $this->key = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : $configured;
        $this->encrypter = new Encrypter($this->key, (string) config('vault.cipher', 'AES-256-CBC'));
    }

    /**
     * Legt de koppeling vast (stap 3/4: ontvangst en registratie).
     */
    public function link(int $sampleId, int $editionId, string $entryUlid, string $reason): VaultLink
    {
        $this->assertAllowed();

        $link = VaultLink::query()->create([
            'sample_id' => $sampleId,
            'edition_id' => $editionId,
            'entry_ref' => $this->encrypter->encryptString($entryUlid),
            'entry_lookup' => $this->lookup($entryUlid),
            'created_by' => $this->userId(),
        ]);

        $this->log('link', $reason, $sampleId, $link->entry_lookup);

        return $link;
    }

    public function entryUlidForSample(int $sampleId, string $reason): ?string
    {
        $this->assertAllowed();

        $link = VaultLink::query()->where('sample_id', $sampleId)->first();
        $this->log('read', $reason, $sampleId, $link?->entry_lookup);

        return $link ? $this->encrypter->decryptString($link->entry_ref) : null;
    }

    public function sampleIdForEntry(string $entryUlid, string $reason): ?int
    {
        $this->assertAllowed();

        $lookup = $this->lookup($entryUlid);
        $link = VaultLink::query()->where('entry_lookup', $lookup)->orderByDesc('id')->first();
        $this->log('read', $reason, $link?->sample_id, $lookup);

        return $link?->sample_id;
    }

    /**
     * @param  list<int>  $sampleIds
     * @return array<int, string> sample_id => entry ulid
     */
    public function entryUlidsForSamples(array $sampleIds, string $reason): array
    {
        $this->assertAllowed();

        $result = [];

        foreach (VaultLink::query()->whereIn('sample_id', $sampleIds)->get() as $link) {
            $result[$link->sample_id] = $this->encrypter->decryptString($link->entry_ref);
            $this->log('read', $reason, $link->sample_id, $link->entry_lookup);
        }

        return $result;
    }

    /**
     * @param  list<string>  $entryUlids
     * @return array<string, int> entry ulid => sample_id
     */
    public function sampleIdsForEntries(array $entryUlids, string $reason): array
    {
        $this->assertAllowed();

        $lookups = [];

        foreach ($entryUlids as $ulid) {
            $lookups[$this->lookup($ulid)] = $ulid;
        }

        $result = [];

        foreach (VaultLink::query()->whereIn('entry_lookup', array_keys($lookups))->orderBy('id')->get() as $link) {
            $result[$lookups[$link->entry_lookup]] = $link->sample_id;
            $this->log('read', $reason, $link->sample_id, $link->entry_lookup);
        }

        return $result;
    }

    /**
     * Alle monsters van één inschrijving (provinciale ronde, beslisronde, finale), oudste eerst.
     *
     * @return list<int>
     */
    public function sampleIdsForEntry(string $entryUlid, string $reason): array
    {
        $this->assertAllowed();

        $lookup = $this->lookup($entryUlid);
        $ids = VaultLink::query()->where('entry_lookup', $lookup)->orderBy('id')->pluck('sample_id')->map(fn ($id) => (int) $id)->all();
        $this->log('read', $reason, null, $lookup);

        return $ids;
    }

    public function hasLinkForEntry(string $entryUlid): bool
    {
        return VaultLink::query()->where('entry_lookup', $this->lookup($entryUlid))->exists();
    }

    /**
     * Zoeksleutel zonder de inhoud prijs te geven.
     */
    public function lookup(string $entryUlid): string
    {
        return hash_hmac('sha256', $entryUlid, $this->key);
    }

    /**
     * Voert een systeemproces uit (uitserveerschema, koppeling na definitieve score) dat de kluis
     * mag raadplegen los van de ingelogde gebruiker. Alleen te gebruiken vanuit jobs en listeners.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function asSystem(callable $callback): mixed
    {
        $previous = $this->systemContext;
        $this->systemContext = true;

        try {
            return $callback();
        } finally {
            $this->systemContext = $previous;
        }
    }

    private function assertAllowed(): void
    {
        if ($this->systemContext) {
            return;
        }

        $user = auth()->user();

        if ($user === null) {
            return; // systeemproces (console, queue)
        }

        if (! $user instanceof User) {
            throw new VaultAccessDeniedException('Alleen medewerkers met de juiste rol mogen de kluis raadplegen.');
        }

        if (! $user->hasAnyRole([StaffRole::Intake->value, StaffRole::Publisher->value])) {
            throw new VaultAccessDeniedException('Deze rol heeft geen toegang tot de kluis.');
        }
    }

    private function log(string $action, string $reason, ?int $sampleId, ?string $entryLookup): void
    {
        $user = $this->systemContext ? null : auth()->user();

        VaultAccessLog::query()->create([
            'user_id' => $this->systemContext ? null : $this->userId(),
            'actor' => $user?->email ?? 'system',
            'action' => $action,
            'sample_id' => $sampleId,
            'entry_lookup' => $entryLookup,
            'reason' => mb_substr($reason, 0, 250),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);

        $this->watchThreshold($user?->email ?? 'system');
    }

    /**
     * Melding bij ongebruikelijk veel inzages per uur door één actor.
     */
    private function watchThreshold(string $actor): void
    {
        $threshold = (int) config('vault.alert_threshold_per_hour', 200);

        if ($threshold <= 0) {
            return;
        }

        $count = VaultAccessLog::query()
            ->where('actor', $actor)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($count === $threshold + 1) {
            Log::warning('Kluis: ongebruikelijk veel inzages', ['actor' => $actor, 'count' => $count]);
        }
    }

    private function userId(): ?int
    {
        $user = auth()->user();

        return $user instanceof User ? (int) $user->getKey() : null;
    }
}
