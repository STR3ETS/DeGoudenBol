<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Filament\Resources\VoucherCampaigns\VoucherCampaignResource;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cadeaubonnenacties van dit bedrijf: status, winnaars en deadlines.
 */
class VoucherCampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'voucherCampaigns';

    protected static ?string $modelLabel = 'cadeaubonnenactie';

    protected static ?string $pluralModelLabel = 'cadeaubonnenacties';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Cadeaubonnen';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return VoucherCampaignResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('edition')->withCount(['winners', 'vouchers']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('edition.name')->label('Editie'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('winners_count')->label('Winnaars')->formatStateUsing(fn (int $state, VoucherCampaign $record) => "{$state} van {$record->winner_count}"),
                TextColumn::make('voucher_value_cents')->label('Waarde per bon')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('vouchers_count')->label('Bonnen'),
                TextColumn::make('winners_deadline_at')->label('Winnaars uiterlijk')->dateTime('d-m-Y H:i'),
                TextColumn::make('last_redeem_day')->label('Laatste verzilverdag')->date('d-m-Y'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Bekijken')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (VoucherCampaign $record) => VoucherCampaignResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Geen cadeaubonnenactie')
            ->emptyStateDescription('Acties ontstaan bij de bevriezing voor de winnaars van de lijsten.');
    }
}
