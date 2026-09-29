<?php

namespace App\Filament\Resources\Profiles\Pages;

use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Profile;
use App\Filament\Resources\Profiles\ProfileResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageProfiles extends ManageRecords
{
    protected static string $resource = ProfileResource::class;

    /**
     * De wachtrij eerst: teksten die nog gekeurd moeten worden.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [];

        foreach ([
            'keuren' => ['Te keuren', ModerationStatus::Pending],
            'goedgekeurd' => ['Goedgekeurd', ModerationStatus::Approved],
            'afgekeurd' => ['Afgekeurd', ModerationStatus::Rejected],
        ] as $key => [$label, $status]) {
            $tabs[$key] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('moderation_status', $status))
                ->badge(function () use ($status): ?int {
                    $count = Profile::query()->where('moderation_status', $status)->count();

                    return $count > 0 ? $count : null;
                });
        }

        $tabs['alle'] = Tab::make('Alle');

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'keuren';
    }
}
