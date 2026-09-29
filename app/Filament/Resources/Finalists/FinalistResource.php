<?php

namespace App\Filament\Resources\Finalists;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\RespondToFinalInvitation;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Models\Finalist;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Finalists\Pages\ManageFinalists;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use UnitEnum;

/**
 * Finalisten: provinciewinnaars (of nummer 2 bij afmelding) en hun deelname aan de landelijke finale.
 */
class FinalistResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Finalist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Finale';

    protected static ?string $modelLabel = 'finalist';

    protected static ?string $pluralModelLabel = 'finalisten';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Publisher, StaffRole::Coordinator, StaffRole::Communication];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.company', 'province', 'replacedBy.entry']))
            ->defaultSort('province_id')
            ->columns([
                TextColumn::make('province.name')->label('Provincie')->sortable(),
                TextColumn::make('entry.public_name')->label('Finalist')->weight('bold')->searchable(),
                TextColumn::make('origin')->label('Herkomst')->formatStateUsing(fn (Finalist $record) => $record->originLabel()),
                TextColumn::make('province_position')->label('Plaats provincie'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('responded_at')->label('Gereageerd')->dateTime('d-m H:i')->placeholder('–'),
                TextColumn::make('replacedBy.entry.public_name')->label('Vervangen door')->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('status')->label('Status')->options(FinalistStatus::class),
            ])
            ->recordActions([
                Action::make('confirm')->label('Bevestigen')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Finalist $record) => $record->status === FinalistStatus::Invited)
                    ->requiresConfirmation()
                    ->action(fn (Finalist $record, RespondToFinalInvitation $respond) => self::run(fn () => $respond($record, true, auth()->user()), 'Deelname bevestigd')),
                Action::make('decline')->label('Afmelden')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (Finalist $record) => $record->status->participates())
                    ->requiresConfirmation()
                    ->modalDescription('Volgens de instelling gaat de plek naar nummer 2 van de bevroren lijst; de provinciale titel blijft bij nummer 1.')
                    ->action(fn (Finalist $record, RespondToFinalInvitation $respond) => self::run(fn () => $respond($record, false, auth()->user()), 'Afgemeld voor de finale')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinalists::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
