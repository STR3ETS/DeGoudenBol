<?php

namespace App\Models;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\RoleAssignmentGuard;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

/**
 * Medewerkersaccount (organisatie). Deelnemers en sponsoren krijgen een eigen model in hun domein.
 */
#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles {
        assignRole as protected assignRoleWithoutGuard;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Panelleden werken uitsluitend in de panel-app, nooit in de backoffice.
        return $this->is_active && $this->hasAnyRole(StaffRole::values()) && $this->staffRoles() !== [StaffRole::Panelist];
    }

    /**
     * Kent rollen toe, maar weigert de verboden combinaties uit het blind protocol.
     */
    public function assignRole(...$roles): static
    {
        app(RoleAssignmentGuard::class)->assertCanAssign($this, ...$roles);

        return $this->assignRoleWithoutGuard(...$roles);
    }

    /**
     * @return list<StaffRole>
     */
    public function staffRoles(): array
    {
        return $this->getRoleNames()
            ->map(fn (string $name) => StaffRole::tryFrom($name))
            ->filter()
            ->values()
            ->all();
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
