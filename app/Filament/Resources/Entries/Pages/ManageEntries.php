<?php

namespace App\Filament\Resources\Entries\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Filament\Resources\Entries\EntryResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageEntries extends ManageRecords
{
    protected static string $resource = EntryResource::class;

    /**
     * Werkvoorraad-tabs op status; de tellers gelden voor de huidige editie.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = ['alle' => Tab::make('Alle')];

        foreach ([
            'betaling' => ['Wacht op betaling', [EntryStatus::PendingPayment]],
            'bevestigd' => ['Bevestigd', [EntryStatus::Registered]],
            'ingepland' => ['Ingepland', [EntryStatus::Scheduled]],
            'in-test' => ['In test', [EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed, EntryStatus::Linked]],
            'gepubliceerd' => ['Gepubliceerd', [EntryStatus::Published, EntryStatus::Confidential]],
            'vervallen' => ['Vervallen', [EntryStatus::Withdrawn, EntryStatus::Cancelled, EntryStatus::FreshnessExpired]],
        ] as $key => [$label, $statuses]) {
            $tabs[$key] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
                ->badge(fn (): ?int => $this->countForCurrentEdition($statuses));
        }

        return $tabs;
    }

    /**
     * @param  list<EntryStatus>  $statuses
     */
    private function countForCurrentEdition(array $statuses): ?int
    {
        $edition = Edition::current();

        if ($edition === null) {
            return null;
        }

        $count = Entry::query()->where('edition_id', $edition->getKey())->whereIn('status', $statuses)->count();

        return $count > 0 ? $count : null;
    }
}
