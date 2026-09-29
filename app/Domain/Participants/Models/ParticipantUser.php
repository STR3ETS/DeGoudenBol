<?php

namespace App\Domain\Participants\Models;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Support\Models\Concerns\NormalizesDatesToUtc;
use Database\Factories\ParticipantUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Account van een deelnemer of medewerker van een deelnemend bedrijf.
 * Logt in op het portaal via de guard 'participant'; nooit op de backoffice.
 */
#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class ParticipantUser extends Authenticatable
{
    /** @use HasFactory<ParticipantUserFactory> */
    use HasFactory, NormalizesDatesToUtc, Notifiable;

    protected $table = 'participant_users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->using(CompanyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleIn(Company $company): ?CompanyUserRole
    {
        $pivot = $this->companies()->whereKey($company->getKey())->first()?->pivot;

        return $pivot?->role;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }
}
