<?php

namespace App\Filament\Resources\VisibilitySpots;

use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\VisibilitySpots\Pages\ManageVisibilitySpots;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Verkochte zichtbaarheidsplekken per provincie. Puur marketing; staat los van de test.
 */
class VisibilitySpotResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = VisibilitySpot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Zichtbaarheidsplekken';

    protected static ?string $modelLabel = 'zichtbaarheidsplek';

    protected static ?string $pluralModelLabel = 'zichtbaarheidsplekken';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance, StaffRole::Coordinator, StaffRole::Communication];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['province', 'entry', 'order.invoice']))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('province.name')->label('Provincie')->sortable(),
                TextColumn::make('position')->label('Plek')->sortable(),
                TextColumn::make('entry.public_name')->label('Bakker')->searchable(),
                TextColumn::make('price_cents')->label('Prijs excl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('betaalstatus')->label('Status')->badge()
                    ->state(fn (VisibilitySpot $record) => match (true) {
                        $record->isPaid() => 'Betaald',
                        $record->reserved_until?->isFuture() => 'Wacht op betaling',
                        default => 'Vervallen',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Betaald' => 'success',
                        'Wacht op betaling' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('order.invoice.number')->label('Factuur')->placeholder('–'),
                TextColumn::make('paid_at')->label('Betaald op')->dateTime('d-m-Y H:i')->placeholder('–'),
                TextColumn::make('ends_at')->label('Zichtbaar tot')->date('d-m-Y'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('province')->label('Provincie')->relationship('province', 'name'),
            ])
            ->recordActions([
                Action::make('release')
                    ->label('Plek vrijgeven')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn () => (bool) auth()->user()?->hasAnyRole([StaffRole::Admin->value, StaffRole::Finance->value]))
                    ->requiresConfirmation()
                    ->modalDescription('De bakker verdwijnt uit "Uitgelicht" en de plek komt weer vrij. Terugbetalen gaat apart, via de boekhouding.')
                    ->action(function (VisibilitySpot $record, AuditLogger $audit): void {
                        $audit->record('visibility_spot.released', $record, ['by' => auth()->user()?->email, 'position' => $record->position, 'entry' => $record->entry_id]);
                        $record->delete();
                        Notification::make()->title('Plek vrijgegeven')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisibilitySpots::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
