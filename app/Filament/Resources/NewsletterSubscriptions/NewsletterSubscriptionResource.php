<?php

namespace App\Filament\Resources\NewsletterSubscriptions;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\NewsletterSubscription;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\NewsletterSubscriptions\Pages\ManageNewsletterSubscriptions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Nieuwsbrief-opt-ins (dubbele opt-in). Export naar het mailplatform volgt zodra dat bekend is.
 */
class NewsletterSubscriptionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = NewsletterSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Nieuwsbrief';

    protected static ?string $modelLabel = 'nieuwsbriefinschrijving';

    protected static ?string $pluralModelLabel = 'nieuwsbrief';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('source')->label('Bron'),
                TextColumn::make('created_at')->label('Aangemeld')->dateTime('d-m-Y H:i'),
                TextColumn::make('confirmed_at')->label('Bevestigd')->dateTime('d-m-Y H:i')->placeholder('Nog niet'),
                TextColumn::make('unsubscribed_at')->label('Afgemeld')->dateTime('d-m-Y H:i')->placeholder('–'),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Actief')->queries(
                    true: fn (Builder $query) => $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'),
                    false: fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('confirmed_at')->orWhereNotNull('unsubscribed_at')),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNewsletterSubscriptions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
