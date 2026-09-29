<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Platform\Enums\StaffRole;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-mailadres')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Wachtwoord')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(12)
                            ->helperText('Laat leeg om het wachtwoord niet te wijzigen. Tweestapsverificatie stelt de medewerker zelf in bij de eerste login.'),
                        Toggle::make('is_active')
                            ->label('Actief')
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make('Rollen')
                    ->description('Een panellid is nooit ook ontvangst/registratie of publicatie (blind protocol).')
                    ->schema([
                        Select::make('roles')
                            ->label('Rollen')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => StaffRole::tryFrom($record->name)?->getLabel() ?? $record->name)
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    $names = Role::query()->whereIn('id', (array) $value)->pluck('name');
                                    $roles = $names->map(fn (string $name) => StaffRole::tryFrom($name))->filter()->values();

                                    foreach ($roles as $first) {
                                        foreach ($roles as $second) {
                                            if ($first !== $second && $first->conflictsWith($second)) {
                                                $fail(sprintf('De rollen "%s" en "%s" mogen niet samenvallen.', $first->getLabel(), $second->getLabel()));

                                                return;
                                            }
                                        }
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}
