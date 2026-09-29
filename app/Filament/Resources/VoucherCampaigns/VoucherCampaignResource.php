<?php

namespace App\Filament\Resources\VoucherCampaigns;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\VoucherCampaigns\Pages\ListVoucherCampaigns;
use App\Filament\Resources\VoucherCampaigns\Pages\ViewVoucherCampaign;
use App\Filament\Resources\VoucherCampaigns\RelationManagers\VouchersRelationManager;
use App\Filament\Resources\VoucherCampaigns\RelationManagers\WinnersRelationManager;
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

/**
 * Bonbeheer: acties per Top 10-ondernemer, winnaars, uitgegeven bonnen, tekorten en rapportage.
 */
class VoucherCampaignResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = VoucherCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Cadeaubonnen';

    protected static ?string $modelLabel = 'cadeaubonnenactie';

    protected static ?string $pluralModelLabel = 'cadeaubonnenacties';

    protected static function allowedRoles(): array
    {
        return [StaffRole::VoucherManager, StaffRole::Finance];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Actie')->columns(4)->schema([
                    TextEntry::make('company.name')->label('Ondernemer'),
                    TextEntry::make('province.name')->label('Provincie'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('terms.version')->label('Actievoorwaarden')->placeholder('Geen versie vastgelegd'),
                    TextEntry::make('starts_at')->label('Start')->dateTime('D d-m-Y H:i'),
                    TextEntry::make('winners_deadline_at')->label('Winnaars invoeren tot')->dateTime('D d-m-Y H:i'),
                    TextEntry::make('last_redeem_day')->label('Laatste verzilverdag')->date('D d-m-Y'),
                    TextEntry::make('voucher_value_cents')->label('Waarde per bon')->formatStateUsing(fn (int $state) => Money::format($state)),
                ]),
                Section::make('Rapportage')->columns(5)->schema([
                    TextEntry::make('report_winners')->label('Winnaars ingevoerd')->state(fn (VoucherCampaign $record) => $record->winners()->count().' / '.$record->winner_count),
                    TextEntry::make('report_issued')->label('Bonnen uitgegeven')->state(fn (VoucherCampaign $record) => $record->vouchers()->count()),
                    TextEntry::make('report_redeemed')->label('Verzilverd')->state(fn (VoucherCampaign $record) => $record->vouchers()->where('status', VoucherStatus::Redeemed)->count()),
                    TextEntry::make('report_expired')->label('Verlopen')->state(fn (VoucherCampaign $record) => $record->vouchers()->where('status', VoucherStatus::Expired)->count()),
                    TextEntry::make('report_shortfall')->label('Tekort (door te factureren)')->state(fn (VoucherCampaign $record) => (int) $record->shortfalls()->sum('count').' bonnen'.($record->shortfalls()->whereNull('invoiced_at')->exists() ? ' · nog niet gefactureerd' : '')),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['company', 'province'])
                ->withCount(['winners', 'vouchers', 'vouchers as redeemed_count' => fn (Builder $q) => $q->where('status', VoucherStatus::Redeemed)])
                ->withSum('shortfalls', 'count'))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('company.name')->label('Ondernemer')->weight('bold')->searchable(),
                TextColumn::make('province.name')->label('Provincie')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('starts_at')->label('Start')->dateTime('d-m H:i')->sortable(),
                TextColumn::make('winners_deadline_at')->label('Deadline winnaars')->dateTime('d-m H:i'),
                TextColumn::make('winners_count')->label('Winnaars')->formatStateUsing(fn (VoucherCampaign $record) => "{$record->winners_count} / {$record->winner_count}"),
                TextColumn::make('vouchers_count')->label('Uitgegeven'),
                TextColumn::make('redeemed_count')->label('Verzilverd'),
                TextColumn::make('shortfalls_sum_count')->label('Tekort')->placeholder('0'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(CampaignStatus::class),
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WinnersRelationManager::class,
            VouchersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoucherCampaigns::route('/'),
            'view' => ViewVoucherCampaign::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
