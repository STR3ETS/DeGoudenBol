<?php

namespace App\Filament\Resources\NewsletterSubscriptions\Pages;

use App\Filament\Resources\NewsletterSubscriptions\NewsletterSubscriptionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageNewsletterSubscriptions extends ManageRecords
{
    protected static string $resource = NewsletterSubscriptionResource::class;
}
