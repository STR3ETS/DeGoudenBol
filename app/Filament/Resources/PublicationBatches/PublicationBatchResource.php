<?php

namespace App\Filament\Resources\PublicationBatches;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\PublicationBatches\Pages\ListPublicationBatches;
use App\Filament\Resources\PublicationBatches\Pages\ViewPublicationBatch;
use App\Filament\Resources\PublicationBatches\RelationManagers\ItemsRelationManager;
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
 * Publicatiebatches: indienen, twee goedkeuringen, publiceren op het vaste moment.
 */
class PublicationBatchResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = PublicationBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Publicaties';

    protected static ?string $modelLabel = 'publicatiebatch';

    protected static ?string $pluralModelLabel = 'publicaties';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Publisher, StaffRole::Communication];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Batch')->columns(4)->schema([
                    TextEntry::make('scheduled_at')->label('Gepland')->dateTime('D d-m-Y H:i'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('submitter.name')->label('Ingediend door')->placeholder('–'),
                    TextEntry::make('submitted_at')->label('Ingediend op')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('approvals')->label('Goedkeuringen')
                        ->state(fn (PublicationBatch $record) => $record->approvals()->with('user')->get()->map(fn ($approval) => $approval->user?->name.' ('.$approval->approved_at->timezone(config('app.display_timezone'))->format('d-m H:i').')')->all())
                        ->badge()->placeholder('Nog geen')->columnSpan(2),
                    TextEntry::make('published_at')->label('Gepubliceerd op')->dateTime('d-m-Y H:i')->placeholder('–'),
                    TextEntry::make('items_count')->label('Uitslagen')->state(fn (PublicationBatch $record) => $record->items()->count()),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['items', 'approvals'])->with('submitter'))
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('scheduled_at')->label('Gepland')->dateTime('D d-m-Y H:i')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('items_count')->label('Uitslagen')->badge()->color('gray'),
                TextColumn::make('approvals_count')->label('Goedkeuringen')->formatStateUsing(fn (int $state) => "{$state} / ".PublicationBatch::REQUIRED_APPROVALS),
                TextColumn::make('submitter.name')->label('Ingediend door')->placeholder('–'),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m H:i')->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('status')->label('Status')->options(BatchStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicationBatches::route('/'),
            'view' => ViewPublicationBatch::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
