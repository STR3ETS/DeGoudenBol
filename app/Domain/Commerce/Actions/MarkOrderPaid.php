<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Contracts\AccountingGateway;
use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Services\InvoiceNumberGenerator;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Events\EntryRegistered;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Zet een order op betaald, maakt de factuur en bevestigt de inschrijving. Idempotent:
 * een tweede aanroep (webhook én redirect) doet niets.
 */
final class MarkOrderPaid
{
    public function __construct(
        private readonly InvoiceNumberGenerator $numbers,
        private readonly AccountingGateway $accounting,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Order $order, ?CarbonInterface $paidAt = null): Order
    {
        $paidAt ??= now();

        $result = DB::transaction(function () use ($order, $paidAt): ?Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($order->isPaid()) {
                return null;
            }

            $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => $paidAt])->save();

            $this->createInvoice($order, $paidAt);

            $entry = $order->orderable;

            if ($entry instanceof Entry && $entry->status === EntryStatus::PendingPayment) {
                $entry->forceFill([
                    'status' => EntryStatus::Registered,
                    'confirmed_at' => $paidAt,
                    'reservation_expires_at' => null,
                ])->save();
            }

            $this->audit->record('order.paid', $order, ['number' => $order->number, 'total_cents' => $order->total_cents]);

            return $order;
        });

        if ($result === null) {
            return $order->refresh();
        }

        OrderPaid::dispatch($result);

        if ($result->orderable instanceof Entry) {
            EntryRegistered::dispatch($result->orderable);
        }

        return $result;
    }

    private function createInvoice(Order $order, CarbonInterface $paidAt): Invoice
    {
        $company = $order->orderable instanceof Entry ? $order->orderable->company : null;
        $location = $company?->primaryLocation;
        $sponsor = $order->orderable instanceof Sponsor ? $order->orderable : null;

        $invoice = Invoice::query()->create([
            'order_id' => $order->getKey(),
            ...$this->numbers->next($paidAt->year),
            'status' => InvoiceStatus::Paid,
            'issued_at' => $paidAt->toDateString(),
            'due_at' => $paidAt->toDateString(),
            'paid_at' => $paidAt,
            'subtotal_cents' => $order->subtotal_cents,
            'vat_cents' => $order->vat_cents,
            'total_cents' => $order->total_cents,
            'billing_name' => $company?->name ?? $sponsor?->name,
            'billing_address' => $location ? [
                'street' => trim("{$location->street} {$location->house_number}"),
                'postcode' => $location->postcode,
                'city' => $location->city,
                'kvk' => $company?->kvk_number,
            ] : ($sponsor ? [...($sponsor->billing_address ?? []), 'kvk' => $sponsor->kvk_number] : null),
        ]);

        $externalId = $this->accounting->syncInvoice($invoice);

        if ($externalId !== null) {
            $invoice->forceFill(['external_id' => $externalId])->save();
        }

        return $invoice;
    }
}
