<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Schrijft regels naar de append-only auditlog en verifieert de hashketen.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(string $action, ?Model $subject = null, array $payload = [], ?Model $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return DB::transaction(function () use ($action, $subject, $payload, $actor): AuditLog {
            $previous = AuditLog::query()->lockForUpdate()->orderByDesc('id')->first();

            $log = new AuditLog([
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'payload' => $payload,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]);

            $log->created_at = now();
            $log->prev_hash = $previous?->hash;
            $log->hash = $this->hashFor($log);
            $log->save();

            return $log;
        });
    }

    public function hashFor(AuditLog $log): string
    {
        $canonical = json_encode([
            'prev_hash' => $log->prev_hash,
            'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            'actor' => [$log->actor_type, $log->actor_id],
            'action' => $log->action,
            'subject' => [$log->subject_type, $log->subject_id],
            'payload' => $this->sortRecursively($log->payload ?? []),
            'ip' => $log->ip,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $canonical, (string) config('audit.salt'));
    }

    /**
     * Loopt de hele keten na. Geeft het aantal gecontroleerde regels en het id van de eerste breuk terug.
     *
     * @return array{ok: bool, checked: int, broken_id: int|null, reason: string|null}
     */
    public function verify(): array
    {
        $checked = 0;
        $previousHash = null;

        foreach (AuditLog::query()->orderBy('id')->lazy() as $log) {
            $checked++;

            if ($log->prev_hash !== $previousHash) {
                return ['ok' => false, 'checked' => $checked, 'broken_id' => $log->id, 'reason' => 'prev_hash wijkt af van de vorige regel'];
            }

            if (! hash_equals($this->hashFor($log), $log->hash)) {
                return ['ok' => false, 'checked' => $checked, 'broken_id' => $log->id, 'reason' => 'hash komt niet overeen met de inhoud'];
            }

            $previousHash = $log->hash;
        }

        return ['ok' => true, 'checked' => $checked, 'broken_id' => null, 'reason' => null];
    }

    public function latestHash(): ?string
    {
        return AuditLog::query()->orderByDesc('id')->value('hash');
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function sortRecursively(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->sortRecursively($value);
            }
        }

        unset($value);

        if (array_is_list($data)) {
            return $data;
        }

        ksort($data);

        return $data;
    }
}
