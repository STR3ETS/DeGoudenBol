<?php

namespace App\Filament\Resources\Samples\Pages;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Actions\ApproveScoreCorrection;
use App\Domain\Testing\Actions\EnterPaperScorecard;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\InvalidateScorecard;
use App\Domain\Testing\Actions\RecalculateResult;
use App\Domain\Testing\Actions\RequestScoreCorrection;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Domain\Testing\Models\ServingAssignment;
use App\Domain\Testing\Services\ScoreCalculator;
use App\Filament\Resources\Samples\SampleResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Scorecontrole per monster: kaarten nakijken, afwijkers en ontbrekende kaarten, papieren
 * invoer, correcties met vier ogen en de uitslag definitief maken.
 */
class ReviewSample extends ViewRecord
{
    protected static string $resource = SampleResource::class;

    protected string $view = 'filament.samples.review';

    public function getTitle(): string
    {
        return 'Monster '.$this->getRecord()->label();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Sample $sample */
        $sample = $this->getRecord()->fresh(['intake', 'result', 'sessions', 'scorecards.corrections', 'scorecards.panelist', 'assignments.panelist']);
        $model = ScoringModel::query()->where('edition_id', $sample->edition_id)->where('is_active', true)->with('criteria')->first();
        $calculator = app(ScoreCalculator::class);
        $codes = $model?->criteria->pluck('code')->all() ?? [];

        return [
            'sample' => $sample,
            'criteria' => $model?->criteria ?? collect(),
            'cards' => $sample->scorecards->sortBy(fn (Scorecard $card) => $card->panelist->display_code)->values(),
            'effective' => $sample->scorecards->mapWithKeys(fn (Scorecard $card) => [$card->getKey() => $calculator->effectiveScores($card, $codes)]),
            'outliers' => $sample->result?->flags['outliers'] ?? [],
            'pendingAssignments' => $this->pendingAssignments($sample),
            'canReview' => $this->canReview(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label('Herberekenen')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->visible(fn () => $this->canReview() && ! $this->getRecord()->result?->isFinal())
                ->action(function (RecalculateResult $recalculate): void {
                    $recalculate($this->getRecord());
                    Notification::make()->title('Uitslag herberekend')->success()->send();
                }),
            Action::make('finalize')
                ->label('Definitief maken')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Uitslag definitief maken')
                ->modalDescription('Daarna wordt het testnummer via de kluis aan de deelnemer gekoppeld en gaat het resultaat naar de eerstvolgende publicatiebatch.')
                ->visible(fn () => $this->canReview() && ! $this->getRecord()->result?->isFinal() && $this->getRecord()->scorecards()->exists())
                ->action(function (FinalizeResult $finalize): void {
                    $this->run(fn () => $finalize($this->getRecord(), auth()->user()), 'Uitslag definitief gemaakt');
                }),
        ];
    }

    public function invalidateCardAction(): Action
    {
        return Action::make('invalidateCard')
            ->label('Ongeldig verklaren')
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')->label('Reden')->required()->rows(2),
            ])
            ->action(function (array $arguments, array $data, InvalidateScorecard $invalidate): void {
                $card = Scorecard::query()->findOrFail($arguments['card']);
                $this->run(fn () => $invalidate($card, $data['reason'], auth()->user()), 'Kaart ongeldig verklaard');
            });
    }

    public function requestCorrectionAction(): Action
    {
        return Action::make('requestCorrection')
            ->label('Correctie aanvragen')
            ->modalHeading('Invoerfout corrigeren')
            ->modalDescription('Alleen voor aantoonbare invoerfouten; het smaakoordeel verandert nooit. Een andere scorecontroleur keurt de correctie goed.')
            ->fillForm(function (array $arguments): array {
                $card = Scorecard::query()->findOrFail($arguments['card']);

                return ['scores' => $card->scores];
            })
            ->schema(fn (): array => [
                Grid::make(4)->schema($this->scoreInputs()),
                Textarea::make('reason')->label('Reden')->required()->rows(2),
            ])
            ->action(function (array $arguments, array $data, RequestScoreCorrection $request): void {
                $card = Scorecard::query()->findOrFail($arguments['card']);
                $this->run(fn () => $request($card, array_map('intval', $data['scores']), $data['reason'], auth()->user()), 'Correctie aangevraagd; een collega moet goedkeuren');
            });
    }

    public function approveCorrectionAction(): Action
    {
        return Action::make('approveCorrection')
            ->label('Goedkeuren')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (array $arguments, ApproveScoreCorrection $approve): void {
                $correction = ScoreCorrection::query()->findOrFail($arguments['correction']);
                $this->run(fn () => $approve($correction, auth()->user()), 'Correctie goedgekeurd en verwerkt');
            });
    }

    public function rejectCorrectionAction(): Action
    {
        return Action::make('rejectCorrection')
            ->label('Afwijzen')
            ->color('gray')
            ->schema([
                Textarea::make('reason')->label('Reden van afwijzing')->required()->rows(2),
            ])
            ->action(function (array $arguments, array $data, ApproveScoreCorrection $approve): void {
                $correction = ScoreCorrection::query()->findOrFail($arguments['correction']);
                $this->run(fn () => $approve($correction, auth()->user(), approve: false, rejectionReason: $data['reason']), 'Correctie afgewezen');
            });
    }

    public function paperEntryAction(): Action
    {
        return Action::make('paperEntry')
            ->label('Papieren kaart invoeren')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->modalHeading('Papieren kaart invoeren')
            ->modalDescription('Noodroute: twee mensen voeren dezelfde kaart onafhankelijk in. Pas bij twee gelijke invoeren telt de kaart mee.')
            ->visible(fn () => $this->canReview() && ! $this->getRecord()->result?->isFinal() && $this->pendingAssignments($this->getRecord())->isNotEmpty())
            ->schema(fn (): array => [
                Select::make('assignment_id')->label('Panellid')
                    ->options(fn () => $this->pendingAssignments($this->getRecord())->mapWithKeys(fn (ServingAssignment $a) => [$a->getKey() => $a->panelist->display_code])->all())
                    ->required(),
                Grid::make(4)->schema($this->scoreInputs()),
                Textarea::make('strengths')->label('Sterke punten')->rows(2),
                Textarea::make('opportunities')->label('Ontwikkelkansen')->rows(2),
            ])
            ->action(function (array $data, EnterPaperScorecard $enter): void {
                $assignment = ServingAssignment::query()->findOrFail($data['assignment_id']);

                $this->run(function () use ($enter, $assignment, $data): string {
                    $outcome = $enter($assignment, array_map('intval', $data['scores']), $data['strengths'] ?? null, $data['opportunities'] ?? null, auth()->user());

                    return match (true) {
                        $outcome->isConfirmed() => 'Twee gelijke invoeren: de kaart telt mee.',
                        $outcome->mismatch => 'De invoer wijkt af van de eerdere invoer; controleer de kaart en voer opnieuw in.',
                        default => 'Eerste invoer bewaard; een collega voert de kaart als tweede in.',
                    };
                });
            });
    }

    /**
     * @return array<int, TextInput>
     */
    private function scoreInputs(): array
    {
        $model = ScoringModel::query()->where('edition_id', $this->getRecord()->edition_id)->where('is_active', true)->with('criteria')->first();

        return $model?->criteria->map(fn ($criterion) => TextInput::make("scores.{$criterion->code}")
            ->label("{$criterion->name} (max {$criterion->max_points})")
            ->numeric()->integer()->minValue(0)->maxValue($criterion->max_points)->required())->all() ?? [];
    }

    /**
     * @return Collection<int, ServingAssignment>
     */
    private function pendingAssignments(Sample $sample): Collection
    {
        $scored = $sample->scorecards()->pluck('panelist_id')->all();

        return $sample->assignments()->with('panelist')->whereNotIn('panelist_id', $scored)->get();
    }

    private function canReview(): bool
    {
        return auth()->user()?->hasRole(StaffRole::Reviewer->value) ?? false;
    }

    /**
     * Voert een actie uit en vertaalt een domeinfout naar een nette melding.
     */
    private function run(callable $callback, ?string $successTitle = null): void
    {
        try {
            $message = $callback();
            Notification::make()->title($successTitle ?? (is_string($message) ? $message : 'Verwerkt'))->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
