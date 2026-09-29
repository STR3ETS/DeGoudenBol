<?php

namespace App\Filament\Resources\Recognitions;

use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Recognitions\Pages\ManageRecognitions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Erkenningen: ontstaan automatisch uit de ranking; hier alleen inzien, verifiëren en intrekken.
 */
class RecognitionResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Recognition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Badges';

    protected static ?string $modelLabel = 'erkenning';

    protected static ?string $pluralModelLabel = 'erkenningen';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication, StaffRole::Publisher];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['company', 'province', 'edition']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->weight('bold'),
                TextColumn::make('type')->label('Erkenning')->badge(),
                TextColumn::make('province.name')->label('Provincie')->placeholder('–'),
                TextColumn::make('code')->label('Code')->copyable()->fontFamily('mono'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (Recognition $record) => match (true) {
                    $record->isRevoked() => 'Ingetrokken',
                    $record->isEmbargoed() => 'Embargo',
                    $record->isHistoric() => 'Historisch',
                    default => 'Actief',
                })->color(fn (Recognition $record) => match (true) {
                    $record->isRevoked() => 'danger',
                    $record->isEmbargoed() => 'warning',
                    $record->isHistoric() => 'gray',
                    default => 'success',
                }),
                TextColumn::make('embargo_until')->label('Embargo tot')->dateTime('d-m H:i')->placeholder('–'),
                TextColumn::make('valid_until')->label('Geldig t/m')->date('d-m-Y')->placeholder('volgende editie'),
            ])
            ->filters([
                SelectFilter::make('edition_id')->label('Editie')->options(fn () => Edition::query()->orderByDesc('year')->pluck('name', 'id')->all())->default(fn () => Edition::current()?->getKey()),
                SelectFilter::make('type')->label('Erkenning')->options(RecognitionType::class),
            ])
            ->recordActions([
                Action::make('verify')->label('Verificatiepagina')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Recognition $record) => $record->verificationUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Recognition $record) => ! $record->isEmbargoed() && ! $record->isRevoked()),
                Action::make('revoke')->label('Intrekken')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('Reden')->required()->rows(2)])
                    ->visible(fn (Recognition $record) => ! $record->isRevoked() && (auth()->user()?->hasAnyRole([StaffRole::Publisher->value, StaffRole::Admin->value]) ?? false))
                    ->action(function (Recognition $record, array $data, AuditLogger $audit): void {
                        $record->forceFill(['status' => 'revoked'])->save();
                        $audit->record('recognition.revoked', $record, ['reason' => $data['reason']]);
                        Notification::make()->title('Erkenning ingetrokken')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRecognitions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
