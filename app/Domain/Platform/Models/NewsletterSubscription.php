<?php

namespace App\Domain\Platform\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * Nieuwsbrief-opt-in met dubbele bevestiging (ondertekende link). Export naar het mailplatform volgt.
 */
#[Fillable(['email', 'source', 'confirmed_at', 'unsubscribed_at', 'ip'])]
class NewsletterSubscription extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'immutable_datetime',
            'unsubscribed_at' => 'immutable_datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }
}
