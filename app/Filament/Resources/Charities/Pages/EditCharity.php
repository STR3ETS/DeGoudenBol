<?php

namespace App\Filament\Resources\Charities\Pages;

use App\Domain\Charities\Actions\RecordPayout;
use App\Domain\Charities\Actions\ReviewCharity;
use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Resources\Charities\CharityResource;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCharity extends EditRecord
{
    protected static string $resource = CharityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review')->label('In beoordeling nemen')->icon(Heroicon::OutlinedMagnifyingGlass)->color('gray')
                ->visible(fn () => $this->canReview() && $this->can(CharityStatus::Reviewing))
                ->action(fn (ReviewCharity $review) => $this->changeStatus($review, CharityStatus::Reviewing)),
            Action::make('approve')->label('Goedkeuren')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                ->visible(fn () => $this->canReview() && $this->can(CharityStatus::Approved))
                ->schema([
                    CheckboxList::make('checklist')->label('Beoordelingschecklist')->options(config('charities.checklist'))->default(fn () => $this->charity()->review_checklist ?? [])->required(),
                    Textarea::make('note')->label('Toelichting')->rows(2),
                ])
                ->action(fn (array $data, ReviewCharity $review) => $this->changeStatus($review, CharityStatus::Approved, $data['checklist'] ?? [], $data['note'] ?? null)),
            Action::make('alternative')->label('Alternatief in overleg')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('warning')
                ->visible(fn () => $this->canReview() && $this->can(CharityStatus::Alternative))
                ->schema([Textarea::make('note')->label('Toelichting voor de voordrager')->rows(3)->required()])
                ->action(fn (array $data, ReviewCharity $review) => $this->changeStatus($review, CharityStatus::Alternative, null, $data['note'])),
            Action::make('link')->label('Koppelen')->icon(Heroicon::OutlinedLink)->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Het doel is akkoord en de gegevens voor uitbetaling zijn compleet.')
                ->visible(fn () => $this->canReview() && $this->can(CharityStatus::Linked))
                ->action(fn (ReviewCharity $review) => $this->changeStatus($review, CharityStatus::Linked)),
            Action::make('payout')->label('Uitbetaling vastleggen')->icon(Heroicon::OutlinedBanknotes)->color('info')
                ->visible(fn () => (auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::Admin->value]) ?? false) && $this->charity()->status->receivesReservations())
                ->schema([
                    TextInput::make('amount')->label('Bedrag (euro)')->numeric()->minValue(0.01)->required()->default(fn () => max(0, $this->charity()->reservedCents() - $this->charity()->paidCents()) / 100)->helperText(fn () => 'Gereserveerd: '.Money::format($this->charity()->reservedCents()).' · al uitbetaald: '.Money::format($this->charity()->paidCents())),
                    DatePicker::make('paid_at')->label('Betaald op')->default(fn () => now())->required(),
                    TextInput::make('reference')->label('Kenmerk / transactie')->maxLength(120),
                    Textarea::make('note')->label('Notitie')->rows(2),
                ])
                ->action(function (array $data, RecordPayout $record): void {
                    try {
                        $record($this->charity(), (int) round(((float) $data['amount']) * 100), CarbonImmutable::parse($data['paid_at']), $data['reference'] ?? null, $data['note'] ?? null, auth()->user());
                        $this->fillForm();
                        Notification::make()->title('Uitbetaling vastgelegd')->success()->send();
                    } catch (CharityException $exception) {
                        Notification::make()->title('Niet vastgelegd')->body($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /**
     * @param  list<string>|null  $checklist
     */
    private function changeStatus(ReviewCharity $review, CharityStatus $to, ?array $checklist = null, ?string $note = null): void
    {
        try {
            $review($this->charity(), $to, $checklist, $note, auth()->user());
            $this->fillForm();
            Notification::make()->title('Status: '.$to->getLabel())->success()->send();
        } catch (CharityException $exception) {
            Notification::make()->title('Niet gewijzigd')->body($exception->getMessage())->danger()->send();
        }
    }

    private function can(CharityStatus $to): bool
    {
        return in_array($to, $this->charity()->status->allowedTransitions(), true);
    }

    private function canReview(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::Admin->value]) ?? false;
    }

    private function charity(): Charity
    {
        /** @var Charity $charity */
        $charity = $this->getRecord();

        return $charity;
    }
}
