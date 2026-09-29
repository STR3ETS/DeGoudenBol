<?php

namespace Database\Factories;

use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Commerce\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = fake()->unique()->numberBetween(1, 9999);
        $year = (int) now()->year;

        return [
            'order_id' => Order::factory()->paid(),
            'year' => $year,
            'sequence' => $sequence,
            'number' => sprintf('%d-%04d', $year, $sequence),
            'status' => InvoiceStatus::Paid,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->toDateString(),
            'paid_at' => now(),
            'subtotal_cents' => 74500,
            'vat_cents' => 15645,
            'total_cents' => 90145,
        ];
    }
}
