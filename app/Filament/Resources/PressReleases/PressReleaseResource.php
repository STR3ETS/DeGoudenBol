<?php

namespace App\Filament\Resources\PressReleases;

use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Actions\GeneratePressReleases;
use App\Domain\Marketing\Enums\PressMilestone;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\PressReleases\Pages\EditPressRelease;
use App\Filament\Resources\PressReleases\Pages\ListPressReleases;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
 * Persberichten: gegenereerd bij de bevriezing, geredigeerd door Communicatie, perskit onder embargo.
 */
class PressReleaseResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = PressRelease::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Persberichten';

    protected static ?string $modelLabel = 'persbericht';

    protected static ?string $pluralModelLabel = 'persberichten';

    protected static ?string $recordTitleAttribute = 'title';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bericht')->schema([
                    TextInput::make('title')->label('Titel')->required()->maxLength(200),
                    Textarea::make('body')->label('Tekst (platte tekst, alinea\'s met een lege regel)')->required()->rows(28),
                    Hidden::make('updated_by')->default(fn () => auth()->id())->dehydrated(),
                ]),
                Section::make('Embargo en publicatie')->columns(2)->schema([
                    DateTimePicker::make('embargo_until')->label('Embargo tot')->seconds(false),
                    DateTimePicker::make('published_at')->label('Gepubliceerd op')->seconds(false)->helperText('Wordt automatisch gezet bij de reveal van de provincie.'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('province'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->limit(60)->weight('bold'),
                TextColumn::make('province.name')->label('Provincie')->placeholder('Landelijk'),
                TextColumn::make('milestone')->label('Moment'),
                TextColumn::make('embargo_until')->label('Embargo tot')->dateTime('d-m H:i')->placeholder('–'),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m H:i')->placeholder('Nog niet'),
                TextColumn::make('sent_at')->label('Perskit verstuurd')->dateTime('d-m H:i')->placeholder('Nog niet'),
            ])
            ->filters([
                SelectFilter::make('milestone')->label('Moment')->options(PressMilestone::class),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Genereren voor bevroren provincies')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->requiresConfirmation()
                    ->modalDescription('Maakt een eerste versie voor iedere provincie met een bevroren Top 10 die nog geen bericht heeft, en een landelijk bericht zodra de landelijke lijst er is. Bestaande teksten blijven staan.')
                    ->action(function (GeneratePressReleases $generate): void {
                        $edition = Edition::current();
                        $count = $edition ? $generate($edition) : 0;
                        Notification::make()->title("{$count} persberichten gegenereerd")->success()->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPressReleases::route('/'),
            'edit' => EditPressRelease::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
