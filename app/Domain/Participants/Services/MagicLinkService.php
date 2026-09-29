<?php

namespace App\Domain\Participants\Services;

use App\Domain\Participants\Models\ParticipantUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Inloggen zonder wachtwoord: een ondertekende, tijdelijke, eenmalige link.
 */
final class MagicLinkService
{
    public function url(ParticipantUser $user, int $minutes = 60, ?string $redirectTo = null): string
    {
        $token = Str::random(48);

        Cache::put($this->cacheKey($token), ['user' => $user->getKey(), 'redirect' => $redirectTo], now()->addMinutes($minutes));

        return URL::temporarySignedRoute('portaal.magic', now()->addMinutes($minutes), ['token' => $token]);
    }

    /**
     * @return array{user: ParticipantUser, redirect: string|null}|null
     */
    public function consume(string $token): ?array
    {
        $payload = Cache::pull($this->cacheKey($token));

        if (! is_array($payload)) {
            return null;
        }

        $user = ParticipantUser::query()->find($payload['user']);

        return $user ? ['user' => $user, 'redirect' => $payload['redirect'] ?? null] : null;
    }

    private function cacheKey(string $token): string
    {
        return 'magic-link:'.hash('sha256', $token);
    }
}
