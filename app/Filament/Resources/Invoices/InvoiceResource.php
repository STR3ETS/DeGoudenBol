<?php

namespace App\Filament\Resources\Invoices;

use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InvoiceResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyEuro;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Facturen';

    protected static ?string $modelLabel = 'factuur';

    protected static ?string $pluralModelLabel = 'facturen';

    protected static ?string $recordTitleAttribute = 'number';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Finance];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Factuur')->columns(3)->schema([
                    TextEntry::make('number')->label('Nummer'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('issued_at')->label('Factuurdatum')->date('d-m-Y'),
                    TextEntry::make('billing_name')->label('Aan')->placeholder('–'),
                    TextEntry::make('order.number')->label('Order'),
                    TextEntry::make('external_id')->label('Boekhouding')->placeholder('Niet gesynchroniseerd'),
                    TextEntry::make('subtotal_cents')->label('Excl. btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                    TextEntry::make('vat_cents')->label('Btw')->formatStateUsing(fn (int $state) => Money::format($state)),
                    TextEntry::make('total_cents')->label('Totaal')->formatStateUsing(fn (int $state) => Money::format($state)),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->label('Factuur')->searchable()->sortable(),
                TextColumn::make('billing_name')->label('Aan')->searchable()->placeholder('–'),
                TextColumn::make('issued_at')->label('Datum')->date('d-m-Y')->sortable(),
                TextColumn::make('total_cents')->label('Totaal')->formatStateUsing(fn (int $state) => Money::format($state))->alignRight(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('order.number')->label('Order'),
                TextColumn::make('external_id')->label('Boekhouding')->placeholder('–')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(InvoiceStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvoices::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
