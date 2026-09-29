<?php

namespace App\Filament\Resources\TestSessions\Pages;

use App\Filament\Resources\TestSessions\TestSessionResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTestSession extends EditRecord
{
    protected static string $resource = TestSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
