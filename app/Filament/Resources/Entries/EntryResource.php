<?php

namespace App\Filament\Resources\Entries;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Actions\ReviewDiscount;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Entries\Pages\ManageEntries;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use UnitEnum;

class EntryResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Entry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Bakkers';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Inschrijvingen';

    protected static ?string $modelLabel = 'inschrijving';

    protected static ?string $pluralModelLabel = 'inschrijvingen';

    protected static ?string $recordTitleAttribute = 'public_name';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Intake, StaffRole::Coordinator, StaffRole::Communication, StaffRole::Finance, StaffRole::VoucherManager];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('public_name')->label('Naam op de site')->required()->maxLength(120),
                TextInput::make('tagline')->label('Korte omschrijving')->maxLength(140),
                CheckboxList::make('allergens')->label('Allergenen')->options(Allergen::options())->columns(2)->columnSpanFull(),
                Textarea::make('product_notes')->label('Toelichting van de deelnemer')->rows(3)->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inschrijving')->columns(3)->schema([
                    TextEntry::make('public_name')->label('Naam op de site'),
                    TextEntry::make('company.name')->label('Bedrijf'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('edition.year')->label('Editie'),
                    TextEntry::make('province.name')->label('Provincie'),
                    TextEntry::make('package.name')->label('Pakket')->placeholder('–'),
                    TextEntry::make('registered_at')->label('Aangemeld')->dateTime('d-m-Y H:i'),
                    TextEntry::make('confirmed_at')->label('Bevestigd')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('reservation_expires_at')->label('Reservering tot')->dateTime('d-m-Y H:i')->placeholder('–'),
                ]),
                Section::make('Product')->columns(2)->schema([
                    TextEntry::make('allergens')->label('Allergenen')->badge()->formatStateUsing(fn (string $state) => Allergen::tryFrom($state)?->getLabel() ?? $state)->placeholder('Geen opgegeven'),
                    TextEntry::make('product_notes')->label('Toelichting')->placeholder('–'),
                    TextEntry::make('tagline')->label('Korte omschrijving')->placeholder('–'),
                ]),
                Section::make('Tarief en betaling')->columns(3)->schema([
                    TextEntry::make('rate')->label('Tarief')->badge(),
                    IconEntry::make('plain_addon')->label('Extra test naturel')->boolean(),
                    TextEntry::make('discount_status')->label('Kortingsactie')->badge()->placeholder('–'),
                    TextEntry::make('discount_proof_url')->label('Post')->url(fn (Entry $record) => $record->discount_proof_url, shouldOpenInNewTab: true)->placeholder('–'),
                    TextEntry::make('discount_note')->label('Toelichting korting')->placeholder('–'),
                    TextEntry::make('discountReviewer.name')->label('Gekeurd door')->placeholder('–'),
                    TextEntry::make('discount_reviewed_at')->label('Gekeurd op')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('order.number')->label('Order')->placeholder('–'),
                    TextEntry::make('order.status')->label('Orderstatus')->badge()->placeholder('–'),
                    TextEntry::make('order.total_cents')->label('Totaal incl. btw')->formatStateUsing(fn ($state) => Money::format((int) $state))->placeholder('–'),
                    TextEntry::make('order.invoice.number')->label('Factuur')->placeholder('–'),
                ]),
                Section::make('Aanlevering')->columns(3)->schema([
                    TextEntry::make('delivery_method')->label('Methode')->badge()->placeholder('Nog niet gekozen'),
                    TextEntry::make('delivery_code')->label('Deelnemercode')->placeholder('–'),
                    TextEntry::make('deliverySlot.starts_at')->label('Tijdslot')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('pickupRequest.day.date')->label('Ophaalvrijdag')->date('d-m-Y')->placeholder('–'),
                    TextEntry::make('pickupRequest.status')->label('Ophaalstatus')->badge()->placeholder('–'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['company', 'province', 'package', 'order.invoice', 'edition']))
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('public_name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->toggleable(),
                TextColumn::make('province.name')->label('Provincie')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('rate')->label('Tarief')->badge(),
                IconColumn::make('plain_addon')->label('Naturel')->boolean()->tooltip('Extra test naturel oliebol')->toggleable(),
                TextColumn::make('discount_status')->label('Korting')->badge()->placeholder('–')
                    ->url(fn (Entry $record) => $record->discount_proof_url, shouldOpenInNewTab: true)
                    ->tooltip(fn (Entry $record) => $record->discount_proof_url ? 'Open de post' : null),
                TextColumn::make('package.name')->label('Pakket')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order.status')->label('Betaling')->badge()->placeholder('–'),
                TextColumn::make('order.invoice.number')->label('Factuur')->placeholder('–')->toggleable(),
                TextColumn::make('delivery_method')->label('Aanlevering')->badge()->placeholder('–')->toggleable(),
                TextColumn::make('registered_at')->label('Aangemeld')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('edition.year')->label('Editie')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('province')->label('Provincie')->relationship('province', 'name'),
                SelectFilter::make('status')->label('Status')->options(EntryStatus::class)->multiple(),
            ])
            ->recordActions([
                Action::make('approveDiscount')
                    ->label('Korting goedkeuren')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Entry $record) => self::canReviewDiscount($record))
                    ->requiresConfirmation()
                    ->modalHeading('Korting goedkeuren?')
                    ->modalDescription(fn (Entry $record) => 'Bekijk eerst de post'.($record->discount_proof_url ? " ({$record->discount_proof_url})" : '').'. De bakker krijgt een mail met de betaallink voor de actieprijs.')
                    ->action(fn (Entry $record, ReviewDiscount $review) => self::review($record, $review, true)),
                Action::make('rejectDiscount')
                    ->label('Afwijzen')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (Entry $record) => self::canReviewDiscount($record))
                    ->schema([Textarea::make('note')->label('Reden voor de bakker')->required()->rows(3)->placeholder('Bijvoorbeeld: in de post is De Gouden Bol niet getagd.')])
                    ->modalHeading('Korting afwijzen')
                    ->modalDescription('De bakker krijgt de reden per mail en kan opnieuw aanleveren of de standaardprijs betalen.')
                    ->action(fn (Entry $record, array $data, ReviewDiscount $review) => self::review($record, $review, false, $data['note'])),
                ViewAction::make(),
                EditAction::make(),
                Action::make('withdraw')
                    ->label('Terugtrekken')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Inschrijving terugtrekken')
                    ->modalDescription('De deelnemer verdwijnt van de site en de plek in de provincie komt vrij. Het resultaat blijft in het archief.')
                    ->visible(fn (Entry $record) => $record->status->occupiesPlace())
                    ->action(function (Entry $record, AuditLogger $audit): void {
                        $record->forceFill(['status' => EntryStatus::Withdrawn, 'withdrawn_at' => now()])->save();
                        $audit->record('entry.withdrawn', $record, ['by' => auth()->user()?->email]);
                        Notification::make()->title('Inschrijving teruggetrokken')->success()->send();
                    }),
            ]);
    }

    private static function canReviewDiscount(Entry $record): bool
    {
        $user = auth()->user();

        return $record->isPendingPayment()
            && $record->wantsDiscount()
            && in_array($record->discount_status, [DiscountStatus::Submitted, DiscountStatus::AwaitingProof], true)
            && $user !== null
            && $user->hasAnyRole([StaffRole::Admin->value, StaffRole::Finance->value, StaffRole::Communication->value]);
    }

    private static function review(Entry $record, ReviewDiscount $review, bool $approve, ?string $note = null): void
    {
        try {
            $review($record, $approve, auth()->user(), $note);
            Notification::make()->title($approve ? 'Korting goedgekeurd; de bakker krijgt de betaallink' : 'Korting afgewezen; de bakker is gemaild')->success()->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEntries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
