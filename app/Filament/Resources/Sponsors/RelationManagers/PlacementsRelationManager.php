<?php

namespace App\Filament\Resources\Sponsors\RelationManagers;

use App\Domain\Commerce\Actions\CreatePlacement;
use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlacementsRelationManager extends RelationManager
{
    protected static string $relationship = 'placements';

    protected static ?string $modelLabel = 'plaatsing';

    protected static ?string $pluralModelLabel = 'plaatsingen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Plaatsingen';
    }

    public function form(Schema $schema): Schema
    {
        $edition = Edition::current();

        return $schema
            ->components([
                Select::make('product_id')->label('Product')
                    ->options(fn () => Product::query()->where('edition_id', $edition?->getKey())->orderBy('sort')->get()->mapWithKeys(fn (Product $product) => [$product->getKey() => $product->name.($product->is_custom ? '' : ' – '.Money::format($product->price_cents))])->all())
                    ->required()->live(),
                Select::make('location')->label('Plek')->options(ProductCode::customLocations())
                    ->visible(fn (Get $get) => $this->code($get) === ProductCode::Custom)->required(fn (Get $get) => $this->code($get) === ProductCode::Custom)->live(),
                Select::make('province_id')->label('Provincie')->options(fn () => Province::query()->orderBy('sort')->pluck('name', 'id')->all())
                    ->visible(fn (Get $get) => $this->code($get)?->requiresProvince() || ($this->code($get) === ProductCode::Custom && $get('location') === 'province'))
                    ->required(fn (Get $get) => $this->code($get)?->requiresProvince() || ($this->code($get) === ProductCode::Custom && $get('location') === 'province')),
                Select::make('entry_id')->label('Deelnemer')
                    ->options(fn () => Entry::query()->where('edition_id', $edition?->getKey())->confirmed()->orderBy('public_name')->pluck('public_name', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get) => (bool) $this->code($get)?->requiresEntry())->required(fn (Get $get) => (bool) $this->code($get)?->requiresEntry()),
                TextInput::make('label')->label('Omschrijving (maatwerk)')->maxLength(160)->visible(fn (Get $get) => $this->code($get) === ProductCode::Custom),
                TextInput::make('price_cents')->label('Prijs excl. btw (euro)')->numeric()->minValue(0)->visible(fn (Get $get) => (bool) Product::query()->find($get('product_id'))?->is_custom)->helperText('Maatwerkprijs; voor vaste producten geldt de catalogusprijs.'),
                DateTimePicker::make('starts_at')->label('Van')->seconds(false)->default(fn () => now())->required(),
                DateTimePicker::make('ends_at')->label('Tot en met')->seconds(false)->default(fn () => ($edition?->main_publication_at ?? now())->addMonths(12))->required(),
                Toggle::make('is_exclusive')->label('Exclusief op deze plek')->visible(fn (Get $get) => $this->code($get) === ProductCode::Custom),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        /** @var Sponsor $sponsor */
        $sponsor = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'province', 'entry', 'orderLine.order']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('product.name')->label('Product')->weight('bold'),
                TextColumn::make('location')->label('Plek')->formatStateUsing(fn (Placement $record) => $record->locationLabel().($record->label ? " · {$record->label}" : '')),
                TextColumn::make('starts_at')->label('Van')->date('d-m-Y'),
                TextColumn::make('ends_at')->label('T/m')->date('d-m-Y'),
                TextColumn::make('price_cents')->label('Prijs')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('orderLine.order.number')->label('Order')->placeholder('nog niet gefactureerd'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Plaatsing reserveren')
                    ->using(function (array $data) use ($sponsor): Placement {
                        $product = Product::query()->findOrFail($data['product_id']);

                        try {
                            $placement = app(CreatePlacement::class)($sponsor, $product, $data);
                        } catch (SponsoringException $exception) {
                            Notification::make()->title('Plaatsing niet gereserveerd')->body($exception->getMessage())->danger()->send();

                            throw new Halt;
                        }

                        if ($product->is_custom && filled($data['price_cents'] ?? null)) {
                            $placement->forceFill(['price_cents' => (int) round(((float) $data['price_cents']) * 100)])->save();
                        }

                        return $placement;
                    }),
            ])
            ->recordActions([
                Action::make('activate')->label('Activeren zonder factuur')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Alleen voor partners die buiten het platform om afrekenen. De 10%-reservering wordt dan niet automatisch berekend.')
                    ->visible(fn (Placement $record) => $record->status === PlacementStatus::Draft && $record->order_line_id === null && (auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::Admin->value]) ?? false))
                    ->action(fn (Placement $record) => $record->forceFill(['status' => PlacementStatus::Active])->save()),
                Action::make('cancel')->label('Annuleren')->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Placement $record) => $record->status->blocksLocation() && (auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::Admin->value]) ?? false))
                    ->action(fn (Placement $record) => $record->forceFill(['status' => PlacementStatus::Cancelled])->save()),
            ]);
    }

    private function code(Get $get): ?ProductCode
    {
        $id = $get('product_id');

        return $id ? Product::query()->find($id)?->code : null;
    }
}
