<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Models\Invoice;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $overdue = fn (Builder $query) => $query->where(fn (Builder $invoice) => $invoice
            ->where('status', InvoiceStatus::Overdue)
            ->orWhere(fn (Builder $open) => $open->where('status', InvoiceStatus::Open)->where('due_at', '<', now())));
        $open = fn (Builder $query) => $query->where('status', InvoiceStatus::Open);
        $paid = fn (Builder $query) => $query->where('status', InvoiceStatus::Paid);
        $credited = fn (Builder $query) => $query->where('status', InvoiceStatus::Credited);

        return [
            'alle' => Tab::make('Alle'),
            'open' => Tab::make('Open')->modifyQueryUsing($open)->badge(fn (): ?int => $this->count($open)),
            'vervallen' => Tab::make('Over de vervaldatum')->modifyQueryUsing($overdue)->badge(fn (): ?int => $this->count($overdue)),
            'betaald' => Tab::make('Betaald')->modifyQueryUsing($paid)->badge(fn (): ?int => $this->count($paid)),
            'gecrediteerd' => Tab::make('Gecrediteerd')->modifyQueryUsing($credited)->badge(fn (): ?int => $this->count($credited)),
        ];
    }

    private function count(callable $scope): ?int
    {
        $count = $scope(Invoice::query())->count();

        return $count > 0 ? $count : null;
    }
}
