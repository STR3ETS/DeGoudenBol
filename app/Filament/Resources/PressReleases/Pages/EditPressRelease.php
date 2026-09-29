<?php

namespace App\Filament\Resources\PressReleases\Pages;

use App\Domain\Marketing\Actions\GeneratePressReleases;
use App\Domain\Marketing\Actions\SendPressKit;
use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Marketing\Models\PressRelease;
use App\Filament\Resources\PressReleases\PressReleaseResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;

class EditPressRelease extends EditRecord
{
    protected static string $resource = PressReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendKit')
                ->label('Perskit versturen')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription(fn () => 'Verstuurt een tijdelijke, ondertekende link naar '.$this->contactCount().' mediacontacten ('.($this->release()->province ? $this->release()->province->name.' en landelijk' : 'alle').'). Onder embargo staat dat groot in de mail en op de pagina.')
                ->action(function (SendPressKit $send): void {
                    $count = $send($this->release(), auth()->user());
                    Notification::make()->title("Perskit verstuurd naar {$count} contacten")->success()->send();
                }),
            Action::make('preview')
                ->label('Bekijk als pers')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn () => $this->release()->isPublished() ? route('pers.toon', $this->release()) : URL::temporarySignedRoute('pers.kit', now()->addHour(), ['pressRelease' => $this->release()->slug]), shouldOpenInNewTab: true),
            Action::make('publishNow')
                ->label('Nu publiceren')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Zet het bericht direct op /pers en laat het embargo vervallen. Doe dit alleen als de uitslag al openbaar is.')
                ->visible(fn () => ! $this->release()->isPublished())
                ->action(function (): void {
                    $this->release()->forceFill(['published_at' => now(), 'embargo_until' => now()])->save();
                    $this->fillForm();
                    Notification::make()->title('Persbericht gepubliceerd')->success()->send();
                }),
            Action::make('regenerate')
                ->label('Tekst opnieuw genereren')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Overschrijft titel en tekst met een nieuwe eerste versie uit de uitslag. Eigen redactie gaat verloren.')
                ->action(function (GeneratePressReleases $generate): void {
                    $generate($this->release()->edition, overwrite: true);
                    $this->fillForm();
                    Notification::make()->title('Tekst opnieuw gegenereerd')->success()->send();
                }),
        ];
    }

    private function release(): PressRelease
    {
        /** @var PressRelease $release */
        $release = $this->getRecord();

        return $release;
    }

    private function contactCount(): int
    {
        $release = $this->release();

        return MediaContact::query()
            ->when($release->province_id !== null, fn ($query) => $query->where(fn ($q) => $q->whereNull('province_id')->orWhere('province_id', $release->province_id)))
            ->count();
    }
}
