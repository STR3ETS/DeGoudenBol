<?php

namespace App\Domain\Participants\Models;

use App\Domain\Participants\Enums\CompanyUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property CompanyUserRole $role
 */
class CompanyUser extends Pivot
{
    protected $table = 'company_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CompanyUserRole::class,
        ];
    }
}
