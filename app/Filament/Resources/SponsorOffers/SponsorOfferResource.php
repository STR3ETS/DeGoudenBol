<?php

namespace App\Filament\Resources\SponsorOffers;

use App\Domain\Commerce\Actions\ReviewSponsorOffer;
use App\Domain\Commerce\Enums\OfferAudience;
use App\Domain\Commerce\Enums\OfferStatus;
use App\Domain\Commerce\Enums\OfferType;
use App\Domain\Commerce\Models\SponsorOffer;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\SponsorOffers\Pages\ManageSponsorOffers;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use UnitEnum;

/**
 * Sponsoracties (briefing 29 okt §10): redactionele goedkeuring voordat een actie op de site of in
 * het portaal verschijnt.
 */
class SponsorOfferResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = SponsorOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Geld';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationLabel = 'Sponsoracties';

    protected static ?string $modelLabel = 'sponsoractie';

    protected static ?string $pluralModelLabel = 'sponsoracties';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication, StaffRole::Finance];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SponsorOffer::query()->where('status', OfferStatus::Submitted)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['sponsor', 'reviewer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Actie')->searchable()->weight('bold')->description(fn (SponsorOffer $record) => $record->description)->wrap(),
                TextColumn::make('sponsor.name')->label('Sponsor')->searchable(),
                TextColumn::make('type')->label('Soort')->badge(),
                TextColumn::make('audience')->label('Voor')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('reviewer.name')->label('Beoordeeld door')->placeholder('–')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(OfferStatus::class)->default(OfferStatus::Submitted->value),
                SelectFilter::make('type')->label('Soort')->options(OfferType::class),
                SelectFilter::make('audience')->label('Voor')->options(OfferAudience::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Goedkeuren')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SponsorOffer $record) => $record->status !== OfferStatus::Approved)
                    ->requiresConfirmation()
                    ->modalDescription('De actie wordt zichtbaar op de sponsorpagina en in het portaal van de doelgroep. De sponsor krijgt bericht.')
                    ->action(fn (SponsorOffer $record, ReviewSponsorOffer $review) => self::review($review, $record, true)),
                Action::make('reject')
                    ->label('Afwijzen')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (SponsorOffer $record) => $record->status !== OfferStatus::Rejected)
                    ->schema([Textarea::make('note')->label('Toelichting voor de sponsor')->required()->rows(3)])
                    ->action(fn (SponsorOffer $record, array $data, ReviewSponsorOffer $review) => self::review($review, $record, false, $data['note'])),
            ]);
    }

    private static function review(ReviewSponsorOffer $review, SponsorOffer $record, bool $approve, ?string $note = null): void
    {
        try {
            $review($record, $approve, auth()->user(), $note);
            Notification::make()->title($approve ? 'Actie goedgekeurd' : 'Actie afgewezen')->success()->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSponsorOffers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
