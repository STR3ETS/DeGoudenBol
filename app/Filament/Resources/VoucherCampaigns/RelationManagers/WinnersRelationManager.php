<?php

namespace App\Filament\Resources\VoucherCampaigns\RelationManagers;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vouchers\Actions\ResendClaimLink;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherWinner;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class WinnersRelationManager extends RelationManager
{
    protected static string $relationship = 'winners';

    protected static ?string $modelLabel = 'winnaar';

    protected static ?string $pluralModelLabel = 'winnaars';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Winnaars';
    }

    public function table(Table $table): Table
    {
        /** @var VoucherCampaign $campaign */
        $campaign = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('voucher'))
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('first_name')->label('Winnaar')->formatStateUsing(fn (VoucherWinner $record) => $record->displayName())->weight('bold'),
                TextColumn::make('email')->label('E-mail')->formatStateUsing(fn (VoucherWinner $record, string $state) => $record->anonymized_at ? '– (geanonimiseerd)' : $state),
                TextColumn::make('created_at')->label('Ingevoerd')->dateTime('d-m H:i'),
                TextColumn::make('claimed_at')->label('Geclaimd')->dateTime('d-m H:i')->placeholder('nog niet'),
                TextColumn::make('voucher.code')->label('Bon')->placeholder('–')->fontFamily('mono'),
                TextColumn::make('voucher.status')->label('Status')->badge()->placeholder('–'),
                IconColumn::make('consent_public_name')->label('Naam publiek')->boolean(),
                IconColumn::make('consent_photo')->label('Foto')->boolean(),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label('Claimlink opnieuw')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->modalDescription('De oude link vervalt; de winnaar ontvangt een nieuwe e-mail.')
                    ->visible(fn (VoucherWinner $record) => ! $record->hasClaimed() && $campaign->status !== CampaignStatus::Expired && (auth()->user()?->hasAnyRole([StaffRole::VoucherManager->value, StaffRole::Admin->value]) ?? false))
                    ->action(function (VoucherWinner $record, ResendClaimLink $resend): void {
                        try {
                            $resend($record, auth()->user());
                            Notification::make()->title('Claimlink opnieuw verstuurd')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
