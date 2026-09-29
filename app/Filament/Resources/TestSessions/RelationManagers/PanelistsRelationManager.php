<?php

namespace App\Filament\Resources\TestSessions\RelationManagers;

use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\TestSession;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PanelistsRelationManager extends RelationManager
{
    protected static string $relationship = 'panelists';

    protected static ?string $modelLabel = 'panellid';

    protected static ?string $pluralModelLabel = 'panelleden';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Panel van deze sessie';
    }

    public function table(Table $table): Table
    {
        /** @var TestSession $session */
        $session = $this->getOwnerRecord();

        return $table
            ->recordTitle(fn (Panelist $record) => $record->display_code)
            ->defaultSort('display_code')
            ->columns([
                TextColumn::make('display_code')->label('Code')->weight('bold'),
                TextColumn::make('pool_status')->label('Pool')->badge(),
                TextColumn::make('allergens')->label('Allergenen')->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)->badge()->color('gray'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Panellid toevoegen')
                    ->recordTitle(fn (Panelist $record) => $record->display_code)
                    ->recordSelectSearchColumns(['display_code'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('pool_status', PoolStatus::Active))
                    ->preloadRecordSelect()
                    ->multiple()
                    ->visible(fn () => $session->status->acceptsScorecards()),
            ])
            ->recordActions([
                DetachAction::make()->label('Uit sessie halen')->visible(fn () => $session->schedule_generated_at === null),
            ]);
    }
}
