<?php

namespace App\Filament\Resources\Entries;

use App\Domain\Participants\Models\Entry;
use App\Support\DutchTime;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inschrijvingen als CSV voor Excel (verzoek Gill 4 nov 2026): contact, e-mail, adres, aanlevering en
 * ophaaladres, zodat de organisatie kan mailen en het ophalen kan plannen. Volgt de filters van de tabel.
 */
final class EntryExport
{
    /**
     * @param  Builder<Entry>  $query
     */
    public static function download(Builder $query): StreamedResponse
    {
        $entries = $query->with(['company.primaryLocation', 'company.users', 'province', 'order', 'deliverySlot', 'pickupRequest.day', 'visibilitySpot'])->get();

        return response()->streamDownload(function () use ($entries): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['naam', 'bedrijf', 'provincie', 'status', 'betaling', 'contactpersoon', 'e-mail', 'telefoon', 'adres', 'postcode', 'plaats', 'aanlevering', 'aanlevermoment', 'ophaalvrijdag', 'ophaaladres', 'toelichting ophalen', 'filmpje', 'extra zichtbaarheid', 'deelnemercode', 'aangemeld'], ';');

            foreach ($entries as $entry) {
                $company = $entry->company;
                $location = $company?->primaryLocation;
                $pickup = $entry->pickupRequest;
                $preference = $entry->pickup_preference ?? [];

                fputcsv($out, [
                    $entry->public_name,
                    $company?->name,
                    $entry->province?->name,
                    $entry->status->getLabel(),
                    $entry->order?->status?->getLabel(),
                    $company?->contact_name,
                    $company?->users->pluck('email')->implode(', '),
                    $company?->contact_phone,
                    $location ? trim($location->street.' '.$location->house_number) : null,
                    $location?->postcode,
                    $location?->city,
                    $entry->delivery_method?->getLabel() ?? ($entry->pickup_prepaid ? 'Laten ophalen' : null),
                    $entry->deliverySlot ? DutchTime::format($entry->deliverySlot->starts_at, 'D-M-YYYY HH:mm') : null,
                    $pickup?->day ? DutchTime::date($pickup->day->date, 'D-M-YYYY') : null,
                    $pickup ? $pickup->address() : (isset($preference['street']) ? trim($preference['street'].' '.($preference['house_number'] ?? '')).', '.($preference['postcode'] ?? '').' '.($preference['city'] ?? '') : null),
                    $pickup?->instructions ?? ($preference['instructions'] ?? null),
                    $entry->video_addon ? 'Ja' : null,
                    $entry->visibilitySpot?->isPaid() ? 'Plek '.$entry->visibilitySpot->position : null,
                    $entry->delivery_code,
                    $entry->registered_at ? DutchTime::format($entry->registered_at, 'D-M-YYYY HH:mm') : null,
                ], ';');
            }

            fclose($out);
        }, 'inschrijvingen-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
