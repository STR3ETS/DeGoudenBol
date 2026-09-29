<?php

namespace App\Filament\Resources\NewsPosts;

use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\NewsPosts\Pages\ManageNewsPosts;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NewsPostResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = NewsPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Campagne';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Nieuws';

    protected static ?string $modelLabel = 'nieuwsbericht';

    protected static ?string $pluralModelLabel = 'nieuws';

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
                    TextInput::make('title')->label('Titel')->required()->maxLength(160),
                    TextInput::make('slug')->label('Slug')->alphaDash()->unique(ignoreRecord: true)->helperText('Leeg laten = automatisch uit de titel.'),
                    Textarea::make('excerpt')->label('Samenvatting')->rows(2)->maxLength(300),
                    RichEditor::make('body')->label('Tekst')->required(),
                    DateTimePicker::make('published_at')->label('Publiceren op')->seconds(false)->helperText('Leeg = concept.'),
                    Hidden::make('author_id')->default(fn () => auth()->id()),
                ]),
                Section::make('SEO')->collapsed()->columns(2)->schema([
                    TextInput::make('meta_title')->label('Meta-titel')->maxLength(70),
                    TextInput::make('meta_description')->label('Meta-omschrijving')->maxLength(160),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('author'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->sortable(),
                TextColumn::make('published_at')->label('Gepubliceerd')->dateTime('d-m-Y H:i')->placeholder('Concept')->sortable(),
                TextColumn::make('author.name')->label('Auteur')->placeholder('–'),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime('d-m-Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published')->label('Gepubliceerd')->queries(
                    true: fn (Builder $query) => $query->whereNotNull('published_at'),
                    false: fn (Builder $query) => $query->whereNull('published_at'),
                ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNewsPosts::route('/'),
        ];
    }
}
