<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Domain\Participants\Models\Company;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Het bakkerdossier: alles van één bedrijf op één pagina, met de relaties als tabbladen.
 */
class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): ?string
    {
        /** @var Company $company */
        $company = $this->getRecord();

        return collect([
            $company->type->getLabel(),
            $company->primaryLocation?->city,
            $company->kvk_number ? "KvK {$company->kvk_number}" : null,
            $company->is_archived ? 'gearchiveerd' : null,
        ])->filter()->implode(' · ');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')
                ->label('Profiel op de site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => route('bakkers.toon', $this->getRecord()), shouldOpenInNewTab: true),
            EditAction::make(),
        ];
    }
}
