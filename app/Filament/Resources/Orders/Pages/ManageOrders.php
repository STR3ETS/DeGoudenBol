<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Edition\Models\Edition;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = ['alle' => Tab::make('Alle')];

        foreach ([
            'open' => ['Wacht op betaling', [OrderStatus::Pending]],
            'betaald' => ['Betaald', [OrderStatus::Paid]],
            'vervallen' => ['Verlopen of geannuleerd', [OrderStatus::Expired, OrderStatus::Cancelled]],
            'terugbetaald' => ['Terugbetaald', [OrderStatus::Refunded]],
        ] as $key => [$label, $statuses]) {
            $tabs[$key] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
                ->badge(fn (): ?int => $this->countForCurrentEdition($statuses));
        }

        return $tabs;
    }

    /**
     * @param  list<OrderStatus>  $statuses
     */
    private function countForCurrentEdition(array $statuses): ?int
    {
        $edition = Edition::current();

        if ($edition === null) {
            return null;
        }

        $count = Order::query()->where('edition_id', $edition->getKey())->whereIn('status', $statuses)->count();

        return $count > 0 ? $count : null;
    }
}
