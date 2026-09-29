<?php

namespace App\Filament\Resources\Objections\Pages;

use App\Domain\Participants\Enums\ObjectionStatus;
use App\Domain\Participants\Models\Objection;
use App\Filament\Resources\Objections\ObjectionResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageObjections extends ManageRecords
{
    protected static string $resource = ObjectionResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [];

        foreach ([
            'open' => ['Open', [ObjectionStatus::Submitted, ObjectionStatus::Reviewing]],
            'gegrond' => ['Gegrond', [ObjectionStatus::Upheld]],
            'ongegrond' => ['Ongegrond', [ObjectionStatus::Dismissed]],
        ] as $key => [$label, $statuses]) {
            $tabs[$key] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
                ->badge(function () use ($statuses): ?int {
                    $count = Objection::query()->whereIn('status', $statuses)->count();

                    return $count > 0 ? $count : null;
                });
        }

        $tabs['alle'] = Tab::make('Alle');

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
