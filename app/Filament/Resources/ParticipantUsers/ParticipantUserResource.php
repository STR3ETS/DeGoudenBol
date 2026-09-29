<?php

namespace App\Filament\Resources\ParticipantUsers;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\MagicLinkNotification;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\ParticipantUsers\Pages\ManageParticipantUsers;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ParticipantUserResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = ParticipantUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Bakkers';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Accounts';

    protected static ?string $modelLabel = 'deelnemersaccount';

    protected static ?string $pluralModelLabel = 'deelnemersaccounts';

    protected static ?string $recordTitleAttribute = 'email';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Intake, StaffRole::Communication, StaffRole::Finance];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                TextInput::make('email')->label('E-mailadres')->email()->required()->unique(ignoreRecord: true)->maxLength(190),
                TextInput::make('phone')->label('Telefoon')->maxLength(32),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('companies'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('companies.name')->label('Bedrijven')->badge()->placeholder('–'),
                IconColumn::make('has_password')->label('Wachtwoord')->state(fn (ParticipantUser $record) => $record->hasPassword())->boolean(),
                TextColumn::make('last_login_at')->label('Laatste login')->dateTime('d-m-Y H:i')->placeholder('–')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('magicLink')
                    ->label('Stuur inloglink')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->action(function (ParticipantUser $record): void {
                        $record->notify(new MagicLinkNotification);
                        Notification::make()->title("Inloglink verstuurd naar {$record->email}")->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageParticipantUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
