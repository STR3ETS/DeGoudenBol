<?php

namespace App\Filament\Resources\PickupRequests;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Actions\UpdatePickupStatus;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\PickupRequests\Pages\ManagePickupRequests;
use App\Support\DutchTime;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Ophaalservice (briefing 29 okt §5): aanvragen per vrijdag en postcode, betaalstatus, chauffeur,
 * routevolgorde, kaartlink en export naar een externe routeplanner.
 */
class PickupRequestResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = PickupRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'Ophaalservice';

    protected static ?string $modelLabel = 'ophaalaanvraag';

    protected static ?string $pluralModelLabel = 'ophaalaanvragen';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Coordinator, StaffRole::Intake, StaffRole::Finance];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PickupRequest::query()->where('status', PickupStatus::Paid)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['day', 'order', 'entry.province'])
                ->orderBy(PickupDay::query()->select('date')->whereColumn('pickup_days.id', 'pickup_requests.pickup_day_id'))
                ->orderByRaw('route_order is null')->orderBy('route_order')->orderBy('postcode'))
            ->columns([
                TextColumn::make('day.date')->label('Vrijdag')->formatStateUsing(fn ($state) => DutchTime::format($state, 'dd D MMM'))->sortable(),
                TextColumn::make('route_order')->label('#')->placeholder('–')->sortable(),
                TextColumn::make('company_name')->label('Bedrijf')->searchable()->weight('bold')
                    ->description(fn (PickupRequest $record) => $record->entry?->province?->name),
                TextColumn::make('postcode')->label('Adres')->searchable()->sortable()
                    ->formatStateUsing(fn (PickupRequest $record) => $record->address())
                    ->url(fn (PickupRequest $record) => $record->mapsUrl(), shouldOpenInNewTab: true),
                TextColumn::make('contact_name')->label('Contact')->description(fn (PickupRequest $record) => $record->phone),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('order.status')->label('Betaling')->badge()->placeholder('–'),
                TextColumn::make('driver_name')->label('Chauffeur')->placeholder('–'),
                TextColumn::make('instructions')->label('Instructies')->limit(40)->placeholder('–')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('pickup_day_id')->label('Vrijdag')->options(fn () => PickupDay::query()->orderBy('date')->get()->mapWithKeys(fn (PickupDay $day) => [$day->getKey() => $day->label()])->all()),
                SelectFilter::make('status')->label('Status')->options(PickupStatus::class)->multiple()->default([PickupStatus::Paid->value, PickupStatus::Scheduled->value, PickupStatus::EnRoute->value, PickupStatus::PickedUp->value]),
                Filter::make('postcode')->label('Postcode begint met')
                    ->schema([TextInput::make('prefix')->label('Postcode begint met')->placeholder('67')])
                    ->query(fn (Builder $query, array $data) => filled($data['prefix'] ?? null) ? $query->where('postcode', 'like', strtoupper(trim($data['prefix'])).'%') : $query),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Route-export (CSV)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->schema([
                        Select::make('day')->label('Vrijdag')->required()
                            ->options(fn () => PickupDay::query()->orderBy('date')->get()->mapWithKeys(fn (PickupDay $day) => [$day->getKey() => $day->label()])->all()),
                    ])
                    ->action(fn (array $data) => self::export(PickupDay::query()->findOrFail($data['day']))),
            ])
            ->recordActions([
                Action::make('plan')
                    ->label('Inplannen')
                    ->icon(Heroicon::OutlinedMap)
                    ->visible(fn (PickupRequest $record) => in_array($record->status, [PickupStatus::Paid, PickupStatus::Scheduled], true))
                    ->fillForm(fn (PickupRequest $record) => ['driver' => $record->driver_name, 'route_order' => $record->route_order])
                    ->schema([
                        TextInput::make('driver')->label('Chauffeur')->required()->maxLength(120),
                        TextInput::make('route_order')->label('Volgorde in de route')->numeric()->minValue(1),
                    ])
                    ->action(fn (PickupRequest $record, array $data, UpdatePickupStatus $update) => self::update($update, $record, PickupStatus::Scheduled, $data['driver'], filled($data['route_order'] ?? null) ? (int) $data['route_order'] : null)),
                Action::make('next')
                    ->label(fn (PickupRequest $record) => match ($record->status->next()) {
                        PickupStatus::EnRoute => 'Onderweg',
                        PickupStatus::PickedUp => 'Opgehaald',
                        PickupStatus::Received => 'Ontvangen',
                        default => 'Volgende stap',
                    })
                    ->icon(Heroicon::OutlinedArrowRightCircle)
                    ->color('success')
                    ->visible(fn (PickupRequest $record) => in_array($record->status, [PickupStatus::Scheduled, PickupStatus::EnRoute, PickupStatus::PickedUp], true))
                    ->action(fn (PickupRequest $record, UpdatePickupStatus $update) => self::update($update, $record, $record->status->next())),
                Action::make('cancel')
                    ->label('Annuleren')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('De plek op deze vrijdag komt vrij. Terugbetalen van de voorrijkosten gaat via de boekhouding.')
                    ->visible(fn (PickupRequest $record) => in_array($record->status, [PickupStatus::Requested, PickupStatus::Paid, PickupStatus::Scheduled], true))
                    ->action(fn (PickupRequest $record, UpdatePickupStatus $update) => self::update($update, $record, PickupStatus::Cancelled)),
            ])
            ->toolbarActions([
                BulkAction::make('planBulk')
                    ->label('Inplannen bij chauffeur')
                    ->icon(Heroicon::OutlinedMap)
                    ->schema([TextInput::make('driver')->label('Chauffeur')->required()->maxLength(120)])
                    ->action(function (Collection $records, array $data, UpdatePickupStatus $update): void {
                        $planned = 0;

                        foreach ($records->values() as $index => $record) {
                            if (in_array($record->status, [PickupStatus::Paid, PickupStatus::Scheduled], true)) {
                                $update($record, PickupStatus::Scheduled, auth()->user(), $data['driver'], $index + 1);
                                $planned++;
                            }
                        }

                        Notification::make()->title("{$planned} adressen ingepland bij {$data['driver']}")->success()->send();
                    }),
            ]);
    }

    private static function update(UpdatePickupStatus $update, PickupRequest $record, ?PickupStatus $status, ?string $driver = null, ?int $order = null): void
    {
        if ($status === null) {
            return;
        }

        try {
            $update($record, $status, auth()->user(), $driver, $order);
            Notification::make()->title('Ophaalaanvraag: '.mb_strtolower($status->getLabel()))->success()->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /**
     * CSV voor een externe routeplanner: één regel per betaald adres, in routevolgorde.
     */
    public static function export(PickupDay $day): StreamedResponse
    {
        $rows = $day->requests()
            ->whereIn('status', [PickupStatus::Paid, PickupStatus::Scheduled, PickupStatus::EnRoute, PickupStatus::PickedUp, PickupStatus::Received])
            ->with('entry')
            ->orderByRaw('route_order is null')->orderBy('route_order')->orderBy('postcode')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['volgorde', 'bedrijf', 'contactpersoon', 'telefoon', 'straat', 'huisnummer', 'postcode', 'plaats', 'deelnemercode', 'chauffeur', 'instructies'], ';');

            foreach ($rows as $row) {
                fputcsv($out, [$row->route_order, $row->company_name, $row->contact_name, $row->phone, $row->street, $row->house_number, $row->postcode, $row->city, $row->entry?->delivery_code, $row->driver_name, $row->instructions], ';');
            }

            fclose($out);
        }, 'ophaalroute-'.$day->date->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePickupRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
