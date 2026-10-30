<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Platform\Services\StaffNotifier;
use App\Filament\Resources\Sponsors\SponsorResource;
use App\Support\Mail\SendOnce;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Sponsoraanmelding via de website (briefing 29 okt §8).
 *
 * - Vaste pakketten (basis sponsor, deelnemerspartner): plaatsingen reserveren en direct een order
 *   voor de Stripe-checkout. Na betaling controleert de organisatie de sponsor voordat hij zichtbaar wordt.
 * - Top 10 partner: een reservering zonder betaalverplichting. De koppeling met een deelnemer volgt
 *   pas als de ranking openbaar is en de deelnemer akkoord geeft; er wordt nooit een plaats gesuggereerd.
 * - Maatwerk (provinciale partner, landelijke partner, hoofdsponsor): een aanvraag zonder prijs.
 *
 * @phpstan-type SponsorData array{company: string, kvk: string|null, contact_name: string, email: string, phone: string|null, street: string, postcode: string, city: string, website: string|null, product: int, entries?: list<int>, province?: int|null, note?: string|null}
 */
final class RegisterSponsor
{
    public function __construct(
        private readonly CreatePlacement $createPlacement,
        private readonly CreateSponsorOrder $createOrder,
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
        private readonly StaffNotifier $staff,
    ) {}

    /**
     * @param  SponsorData  $data
     * @return array{sponsor: Sponsor, order: Order|null}
     */
    public function __invoke(Edition $edition, array $data, ?UploadedFile $logo = null): array
    {
        $product = Product::query()->where('edition_id', $edition->getKey())->findOrFail($data['product']);

        if (! $product->code->onWebsite()) {
            throw new SponsoringException('Dit pakket is niet via de website te bestellen.');
        }

        $direct = in_array($product->code, [ProductCode::Homepage, ProductCode::ParticipantLink], true);
        $entries = array_values(array_unique(array_map('intval', $data['entries'] ?? [])));

        if ($product->code === ProductCode::ParticipantLink && $entries === []) {
            throw new SponsoringException('Kies minstens één deelnemer die uw product of merk gebruikt.');
        }

        if ($product->code === ProductCode::ProvincePartner && blank($data['province'] ?? null)) {
            throw new SponsoringException('Kies de provincie waaraan u zich wilt verbinden.');
        }

        $logoPath = $logo?->store('sponsors', 'public');

        $result = DB::transaction(function () use ($edition, $data, $product, $direct, $entries, $logoPath): array {
            $sponsor = Sponsor::query()->create([
                'name' => trim($data['company']),
                'url' => $data['website'] ?? null,
                'logo_path' => $logoPath,
                'contact_name' => trim($data['contact_name']),
                'contact_email' => mb_strtolower(trim($data['email'])),
                'contact_phone' => $data['phone'] ?? null,
                'kvk_number' => $data['kvk'] ?? null,
                'billing_address' => ['street' => trim($data['street']), 'postcode' => strtoupper(trim($data['postcode'])), 'city' => trim($data['city'])],
                'status' => $direct ? SponsorStatus::Pending : SponsorStatus::Request,
                'source' => 'website',
                'requested_product_id' => $product->getKey(),
                'requested_province_id' => $data['province'] ?? null,
                'request_note' => $data['note'] ?? null,
                'requested_at' => now(),
            ]);

            $this->audit->record('sponsor.registered', $sponsor, ['product' => $product->code->value, 'direct' => $direct], null);

            if (! $direct) {
                return ['sponsor' => $sponsor, 'order' => null];
            }

            $period = ['starts_at' => now(), 'ends_at' => self::seasonEnd($edition), 'notify' => false];

            if ($product->code === ProductCode::Homepage) {
                ($this->createPlacement)($sponsor, $product, $period);
            }

            foreach ($entries as $entryId) {
                ($this->createPlacement)($sponsor, $product, [...$period, 'entry_id' => $entryId]);
            }

            $order = ($this->createOrder)($sponsor, $edition);
            $order->forceFill(['metadata' => ['email' => $sponsor->contact_email]])->save();

            return ['sponsor' => $sponsor, 'order' => $order];
        });

        if ($result['order'] === null) {
            $this->mail->send($result['sponsor']->contact_email, new SponsorMail(SponsorMail::REQUEST, $result['sponsor']), "sponsor:{$result['sponsor']->getKey()}:request");

            $this->staff->notify(
                [StaffRole::Finance, StaffRole::Communication],
                ($product->code === ProductCode::NationalTop ? 'Top 10-reservering' : 'Maatwerkaanvraag').": {$result['sponsor']->name}",
                "{$product->name}. Neem contact op met {$result['sponsor']->contact_name} ({$result['sponsor']->contact_email}).",
                SponsorResource::getUrl('edit', ['record' => $result['sponsor']]),
                null,
                'heroicon-o-sparkles',
                'info',
            );
        }

        return $result;
    }

    /**
     * Zichtbaarheid loopt tot het einde van het seizoen (31 december van het editiejaar).
     */
    public static function seasonEnd(Edition $edition): CarbonImmutable
    {
        return CarbonImmutable::create($edition->year, 12, 31, 23, 59, 59, 'Europe/Amsterdam')->utc();
    }
}
