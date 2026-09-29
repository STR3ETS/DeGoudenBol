<?php

namespace App\Filament\Resources\Entries;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Entries\Pages\ManageEntries;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Section::make('Betaling')->columns(3)->schema([
                    TextEntry::make('order.number')->label('Order')->placeholder('–'),
                    TextEntry::make('order.status')->label('Orderstatus')->badge()->placeholder('–'),
                    TextEntry::make('order.invoice.number')->label('Factuur')->placeholder('–'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['company', 'province', 'package', 'order', 'edition']))
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('public_name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->toggleable(),
                TextColumn::make('province.name')->label('Provincie')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('package.name')->label('Pakket')->toggleable(),
                TextColumn::make('order.status')->label('Betaling')->badge()->placeholder('–'),
                TextColumn::make('registered_at')->label('Aangemeld')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('edition.year')->label('Editie')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('province')->label('Provincie')->relationship('province', 'name'),
                SelectFilter::make('status')->label('Status')->options(EntryStatus::class)->multiple(),
            ])
            ->recordActions([
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
