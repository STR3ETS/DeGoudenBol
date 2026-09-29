<?php

namespace App\Filament\Resources\ConfidentialReports;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Filament\Concerns\RestrictsToRoles;
use App\Filament\Resources\ConfidentialReports\Pages\ManageConfidentialReports;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
 * Redactie van het vertrouwelijke rapport: sterke punten eerst, dan ontwikkelkansen, positieve toon.
 */
class ConfidentialReportResource extends Resource
{
    use RestrictsToRoles;

    protected static ?string $model = ConfidentialReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Uitslag';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Terugkoppeling onder 5,0';

    protected static ?string $modelLabel = 'vertrouwelijk rapport';

    protected static ?string $pluralModelLabel = 'vertrouwelijke rapporten';

    protected static function allowedRoles(): array
    {
        return [StaffRole::Communication, StaffRole::Reviewer, StaffRole::Publisher];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rapport')->description('Het concept komt uit de geanonimiseerde panelnotities. Sterke punten voorop, daarna concrete ontwikkelkansen. Een cursus pas ná de uitslag noemen.')->schema([
                    TextEntry::make('entry.public_name')->label('Deelnemer'),
                    Textarea::make('strengths')->label('Sterke punten')->rows(6),
                    Textarea::make('opportunities')->label('Ontwikkelkansen')->rows(6),
                    Textarea::make('course_suggestion')->label('Suggestie (optioneel)')->rows(2),
                    Select::make('status')->label('Status')->options(ReportStatus::class)->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['entry.province', 'editor']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('entry.public_name')->label('Deelnemer')->searchable()->weight('bold'),
                TextColumn::make('entry.province.name')->label('Provincie'),
                TextColumn::make('entry.status')->label('Inschrijving')->badge(),
                TextColumn::make('status')->label('Rapport')->badge(),
                TextColumn::make('editor.name')->label('Redactie')->placeholder('–'),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime('d-m H:i'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ReportStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label('Redigeren')->mutateDataUsing(function (array $data): array {
                    $data['edited_by'] = auth()->id();
                    $data['published_at'] = ($data['status'] ?? null) === ReportStatus::Published->value ? now() : null;

                    return $data;
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConfidentialReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
