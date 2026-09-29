<?php

namespace App\Filament\Resources\Profiles;

use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Profile;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\Profiles\Pages\ManageProfiles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
 * Moderatie van openbare profielteksten door Communicatie.
 */
class ProfileResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = Profile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Bakkers';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'profiel';

    protected static ?string $pluralModelLabel = 'profielteksten';

    protected static ?string $navigationLabel = 'Profielteksten keuren';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication];
    }

    /**
     * Aantal profielteksten dat op moderatie wacht, als pil in het menu.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = Profile::query()->where('moderation_status', ModerationStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Wacht op moderatie';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ingediende versie')->columns(1)->schema([
                    TextEntry::make('company.name')->label('Bedrijf'),
                    TextEntry::make('tagline')->label('Korte omschrijving')->placeholder('–'),
                    TextEntry::make('story')->label('Verhaal')->placeholder('–')->prose(),
                    TextEntry::make('specialties')->label('Specialiteiten')->badge()->placeholder('–'),
                ]),
                Section::make('Nu op de site')->columns(1)->collapsible()->schema([
                    TextEntry::make('published.tagline')->label('Korte omschrijving')->placeholder('Nog niets gepubliceerd'),
                    TextEntry::make('published.story')->label('Verhaal')->placeholder('–')->prose(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['company', 'reviewer']))
            ->defaultSort(fn (Builder $query) => $query->orderByRaw("case when moderation_status = 'pending' then 0 else 1 end")->orderByDesc('submitted_at'))
            ->columns([
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->sortable(),
                TextColumn::make('moderation_status')->label('Status')->badge(),
                TextColumn::make('tagline')->label('Korte omschrijving')->limit(50)->placeholder('–'),
                TextColumn::make('submitted_at')->label('Ingediend')->dateTime('d-m-Y H:i')->placeholder('–')->sortable(),
                TextColumn::make('reviewer.name')->label('Beoordeeld door')->placeholder('–')->toggleable(),
                TextColumn::make('reviewed_at')->label('Beoordeeld op')->dateTime('d-m-Y H:i')->placeholder('–')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('moderation_status')->label('Status')->options(ModerationStatus::class)->default(ModerationStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Goedkeuren')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Profile $record) => $record->moderation_status !== ModerationStatus::Approved || $record->hasUnpublishedChanges())
                    ->action(function (Profile $record, AuditLogger $audit): void {
                        $record->approve(auth()->user());
                        $audit->record('profile.approved', $record, ['company' => $record->company->slug]);
                        Notification::make()->title('Profiel goedgekeurd en gepubliceerd')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Afkeuren')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('note')->label('Toelichting voor de deelnemer')->required()->rows(3),
                    ])
                    ->visible(fn (Profile $record) => $record->moderation_status === ModerationStatus::Pending)
                    ->action(function (Profile $record, array $data, AuditLogger $audit): void {
                        $record->reject(auth()->user(), $data['note']);
                        $audit->record('profile.rejected', $record, ['company' => $record->company->slug, 'note' => $data['note']]);
                        Notification::make()->title('Profiel afgekeurd')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProfiles::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
