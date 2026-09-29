<?php

namespace App\Filament\Resources\VoucherCampaigns\RelationManagers;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vouchers\Actions\VoidVoucher;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\Voucher;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class VouchersRelationManager extends RelationManager
{
    protected static string $relationship = 'vouchers';

    protected static ?string $modelLabel = 'bon';

    protected static ?string $pluralModelLabel = 'bonnen';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Uitgegeven bonnen';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('winner'))
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Bonnummer')->fontFamily('mono')->weight('bold')->searchable(),
                TextColumn::make('winner.first_name')->label('Winnaar')->formatStateUsing(fn (Voucher $record) => $record->winner?->displayName() ?? '–'),
                TextColumn::make('value_cents')->label('Waarde')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('issued_at')->label('Uitgegeven')->dateTime('d-m H:i'),
                TextColumn::make('expires_at')->label('Geldig t/m')->date('d-m-Y'),
                TextColumn::make('redeemed_at')->label('Verzilverd')->dateTime('d-m H:i')->placeholder('–'),
            ])
            ->recordActions([
                Action::make('void')
                    ->label('Ongeldig maken')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('Reden')->required()->rows(2)])
                    ->visible(fn (Voucher $record) => $record->status === VoucherStatus::Issued && (auth()->user()?->hasAnyRole([StaffRole::VoucherManager->value, StaffRole::Admin->value]) ?? false))
                    ->action(function (Voucher $record, array $data, VoidVoucher $void): void {
                        try {
                            $void($record, $data['reason'], auth()->user());
                            Notification::make()->title('Bon ongeldig gemaakt')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
