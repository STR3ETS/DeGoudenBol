<?php

namespace App\Filament\Resources\Samples\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Filament\Resources\Samples\SampleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSamples extends ListRecords
{
    protected static string $resource = SampleResource::class;

    /**
     * Werkvoorraad van de scorecontrole: eerst wat aandacht vraagt.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $attention = fn (Builder $query) => $query
            ->whereHas('result', fn (Builder $result) => $result->where('status', ResultStatus::Pending))
            ->has('scorecards');
        $waiting = fn (Builder $query) => $query
            ->whereIn('status', [SampleStatus::Numbered, SampleStatus::Scheduled])
            ->doesntHave('scorecards');
        $final = fn (Builder $query) => $query->whereHas('result', fn (Builder $result) => $result->where('status', ResultStatus::Final));
        $expired = fn (Builder $query) => $query->whereIn('status', [SampleStatus::FreshnessExpired, SampleStatus::Void]);

        return [
            'aandacht' => Tab::make('Vraagt aandacht')->modifyQueryUsing($attention)->badge(fn (): ?int => $this->count($attention)),
            'wacht' => Tab::make('Nog geen kaarten')->modifyQueryUsing($waiting)->badge(fn (): ?int => $this->count($waiting)),
            'definitief' => Tab::make('Definitief')->modifyQueryUsing($final)->badge(fn (): ?int => $this->count($final)),
            'vervallen' => Tab::make('Verlopen of ongeldig')->modifyQueryUsing($expired)->badge(fn (): ?int => $this->count($expired)),
            'alle' => Tab::make('Alle'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'aandacht';
    }

    private function count(callable $scope): ?int
    {
        $edition = Edition::current();

        if ($edition === null) {
            return null;
        }

        $count = $scope(Sample::query()->where('edition_id', $edition->getKey()))->count();

        return $count > 0 ? $count : null;
    }
}
