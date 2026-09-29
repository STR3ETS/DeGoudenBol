<?php

namespace App\Filament\Resources\ScoringModels\Pages;

use App\Filament\Resources\ScoringModels\ScoringModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScoringModel extends EditRecord
{
    protected static string $resource = ScoringModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
