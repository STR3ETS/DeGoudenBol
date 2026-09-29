<?php

namespace Database\Seeders;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Models\Entry;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Support\DutchTime;
use Illuminate\Database\Seeder;

/**
 * Alleen lokaal: een open cadeaubonnenactie voor de gepubliceerde demo-deelnemer, met één geclaimde bon
 * (vast token zodat /bon/{token} te openen is) en één winnaar die nog moet claimen.
 * Idempotent: draait alleen als er nog geen actie bestaat.
 */
class LocalVoucherSeeder extends Seeder
{
    public const string DEMO_VOUCHER_TOKEN = 'demo-bon-token-alleen-lokaal-0001';

    public function run(): void
    {
        if (! app()->environment('local') || VoucherCampaign::query()->exists()) {
            return;
        }

        $entry = Entry::query()->whereNotNull('published_at')->with(['company', 'edition', 'province'])->orderBy('id')->first();

        if ($entry === null) {
            return;
        }

        $edition = $entry->edition;
        $terms = TermsVersion::latestPublished(TermsType::VoucherCampaign);
        $codes = app(VoucherCode::class);

        $campaign = VoucherCampaign::query()->create([
            'edition_id' => $edition->getKey(),
            'company_id' => $entry->company_id,
            'entry_id' => $entry->getKey(),
            'province_id' => $entry->province_id,
            'terms_version_id' => $terms?->getKey(),
            'starts_at' => now()->subHours(3),
            'winners_deadline_at' => now()->addDays(2),
            'last_redeem_day' => now()->addDays(7)->toDateString(),
            'winner_count' => $edition->settings->voucherCountPerWinner,
            'voucher_value_cents' => $edition->settings->voucherValueCents,
            'status' => CampaignStatus::Open,
            'opened_notified_at' => now()->subHours(3),
        ]);

        $claimed = $campaign->winners()->create([
            'first_name' => 'Demo',
            'last_initial' => 'W',
            'email' => 'demo-winnaar1@degoudenbol.test',
            'claim_token_hash' => $codes->hash('demo-claim-alleen-lokaal-0001'),
            'claimed_at' => now()->subHour(),
            'terms_version_id' => $terms?->getKey(),
            'consent_public_name' => true,
            'consent_photo' => true,
        ]);

        Voucher::query()->create([
            'code' => $codes->generate($entry->province, $edition->year),
            'qr_token_hash' => $codes->hash(self::DEMO_VOUCHER_TOKEN),
            'voucher_campaign_id' => $campaign->getKey(),
            'voucher_winner_id' => $claimed->getKey(),
            'value_cents' => $campaign->voucher_value_cents,
            'status' => VoucherStatus::Issued,
            'issued_at' => now()->subHour(),
            'expires_at' => DutchTime::display($campaign->last_redeem_day)->endOfDay()->utc(),
        ]);

        $campaign->winners()->create([
            'first_name' => 'Demo',
            'last_initial' => 'T',
            'email' => 'demo-winnaar2@degoudenbol.test',
            'claim_token_hash' => $codes->hash('demo-claim-alleen-lokaal-0002'),
        ]);
    }
}
