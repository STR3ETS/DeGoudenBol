<?php

namespace App\Filament\Resources\Editions\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Provincies binnen een editie: capaciteit en het reveal-moment op de publicatiedag.
 */
class ProvincesRelationManager extends RelationManager
{
    protected static string $relationship = 'provinces';

    protected static ?string $title = 'Provincies';

    protected static ?string $modelLabel = 'provincie';

    protected static ?string $pluralModelLabel = 'provincies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Provincies en capaciteit';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::pivotFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('provinces.sort')
            ->columns([
                TextColumn::make('name')->label('Provincie'),
                TextColumn::make('code')->label('Code'),
                TextColumn::make('pivot.capacity')->label('Capaciteit'),
                TextColumn::make('pivot.reveal_at')->label('Reveal op publicatiedag')->dateTime('d-m-Y H:i')->placeholder('–'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Provincie toevoegen')
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...self::pivotFields(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Aanpassen'),
                DetachAction::make()->label('Verwijderen uit editie'),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function pivotFields(): array
    {
        return [
            TextInput::make('capacity')
                ->label('Capaciteit (plekken)')
                ->numeric()
                ->minValue(1)
                ->required(),
            DateTimePicker::make('reveal_at')
                ->label('Reveal-moment')
                ->seconds(false)
                ->helperText('Tijdstip waarop deze provincie op de publicatiedag live gaat (besluit 20).'),
        ];
    }
}
