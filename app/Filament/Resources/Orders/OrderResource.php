<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use UnitEnum;

class OrderResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'orders';

    protected static ?string $recordTitleAttribute = 'number';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')->columns(3)->schema([
                    TextEntry::make('number')->label('Nummer'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('total_cents')->label('Totaal incl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                    TextEntry::make('orderable.public_name')->label('Inschrijving')->placeholder('–'),
                    TextEntry::make('orderable.company.name')->label('Bedrijf')->placeholder('–'),
                    TextEntry::make('paid_at')->label('Betaald op')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('expires_at')->label('Reservering tot')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('invoice.number')->label('Factuur')->placeholder('–'),
                ]),
                Section::make('Regels')->schema([
                    RepeatableEntry::make('lines')->label('')->schema([
                        TextEntry::make('description')->label('Omschrijving'),
                        TextEntry::make('subtotal_cents')->label('Excl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                        TextEntry::make('vat_cents')->label('Btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                        TextEntry::make('total_cents')->label('Incl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                    ])->columns(4),
                ]),
                Section::make('Betalingen')->schema([
                    RepeatableEntry::make('payments')->label('')->schema([
                        TextEntry::make('provider')->label('Provider'),
                        TextEntry::make('provider_id')->label('Id'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('method')->label('Methode')->placeholder('–'),
                        TextEntry::make('paid_at')->label('Betaald')->dateTime('d-m-Y H:i')->placeholder('–'),
                    ])->columns(5),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['orderable' => fn (MorphTo $morph) => $morph->morphWith([Entry::class => ['company']]), 'invoice', 'latestPayment']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->label('Order')->searchable()->sortable(),
                TextColumn::make('orderable.public_name')->label('Inschrijving')->placeholder('–'),
                TextColumn::make('orderable.name')->label('Sponsor')->placeholder('–'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('total_cents')->label('Totaal')->formatStateUsing(fn (int $state) => Money::format($state))->alignRight(),
                TextColumn::make('latestPayment.status')->label('Laatste betaling')->badge()->placeholder('–'),
                TextColumn::make('invoice.number')->label('Factuur')->placeholder('–'),
                TextColumn::make('created_at')->label('Aangemaakt')->dateTime('d-m-Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(OrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markPaid')
                    ->label('Handmatig betaald')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Order als betaald markeren')
                    ->modalDescription('Alleen bij een bevestigde bankoverschrijving. Er wordt direct een factuur aangemaakt en de inschrijving wordt bevestigd.')
                    ->visible(fn (Order $record) => $record->isPending())
                    ->action(function (Order $record, MarkOrderPaid $markPaid, AuditLogger $audit): void {
                        $audit->record('order.manually_marked_paid', $record, ['by' => auth()->user()?->email]);
                        $markPaid($record);
                        Notification::make()->title('Order betaald en factuur aangemaakt')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
