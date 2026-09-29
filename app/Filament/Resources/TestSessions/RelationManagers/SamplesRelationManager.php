<?php

namespace App\Filament\Resources\TestSessions\RelationManagers;

use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Monsters in de sessie: alleen genummerde monsters van dezelfde editie en ronde die nog nergens zijn ingedeeld.
 */
class SamplesRelationManager extends RelationManager
{
    protected static string $relationship = 'samples';

    protected static ?string $modelLabel = 'monster';

    protected static ?string $pluralModelLabel = 'monsters';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Monsters in deze sessie';
    }

    public function table(Table $table): Table
    {
        /** @var TestSession $session */
        $session = $this->getOwnerRecord();

        return $table
            ->recordTitle(fn (Sample $record) => $record->label())
            ->modifyQueryUsing(fn (Builder $query) => $query->with('intake'))
            ->defaultSort('session_samples.serving_order')
            ->columns([
                TextColumn::make('serving_order')->label('#')->state(fn (Sample $record) => $record->pivot->serving_order),
                TextColumn::make('sample_number')->label('Testnummer')->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('intake.received_at')->label('Ontvangen')->dateTime('d-m H:i')->placeholder('–'),
                TextColumn::make('intake.freshness_expires_at')->label('Vers tot')->dateTime('d-m H:i')->placeholder('–')
                    ->color(fn (Sample $record) => $record->intake?->freshness_expires_at?->lessThan($session->ends_at) ? 'danger' : 'success'),
                TextColumn::make('intake.temperature_c')->label('°C')->placeholder('–'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Monster toevoegen')
                    ->recordTitle(fn (Sample $record) => $record->label())
                    ->recordSelectSearchColumns(['sample_number'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query
                        ->where('edition_id', $session->edition_id)
                        ->where('round', $session->round)
                        ->where('status', SampleStatus::Numbered)
                        ->whereDoesntHave('sessions'))
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('serving_order')->label('Volgorde')->numeric()->minValue(1)
                            ->default(fn () => $session->samples()->count() + 1)->required(),
                    ])
                    ->visible(fn () => $session->status->acceptsScorecards() && $session->samples()->count() < $session->max_samples),
            ])
            ->recordActions([
                DetachAction::make()->label('Uit sessie halen')
                    ->visible(fn () => $session->schedule_generated_at === null)
                    ->after(function (Sample $record): void {
                        if ($record->status === SampleStatus::Scheduled) {
                            $record->forceFill(['status' => SampleStatus::Numbered])->save();
                        }
                    }),
            ]);
    }
}
