<?php

namespace App\Filament\Support;

use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Enums\ObjectionStatus;
use App\Domain\Participants\Models\Objection;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\PublicationItem;
use App\Filament\Resources\Objections\ObjectionResource;
use App\Models\User;
use App\Support\DutchTime;
use Illuminate\Support\Collection;

/**
 * Waar een publicatiebatch staat: de vier stappen, wie al heeft goedgekeurd, wat de volgende
 * stap is voor de ingelogde medewerker en wat publicatie nog blokkeert.
 */
final class PublicationCockpit
{
    public function __construct(private readonly PublicationBatch $batch, private readonly ?User $user) {}

    /**
     * @return list<array{n: int, label: string, state: string, note: string|null}>
     */
    public function steps(): array
    {
        $batch = $this->batch;
        $index = match ($batch->status) {
            BatchStatus::Draft => 0,
            BatchStatus::PendingApproval => 1,
            BatchStatus::Approved => 2,
            BatchStatus::Published => 3,
        };

        $definition = [
            [1, 'In opbouw', $batch->items()->count().' '.($batch->items()->count() === 1 ? 'uitslag' : 'uitslagen')],
            [2, 'Ingediend', $batch->submitted_at ? DutchTime::format($batch->submitted_at, 'D MMM HH:mm') : null],
            [3, 'Goedgekeurd', $this->approvals()->count().' van '.PublicationBatch::REQUIRED_APPROVALS],
            [4, 'Gepubliceerd', $batch->published_at ? DutchTime::format($batch->published_at, 'D MMM HH:mm') : DutchTime::format($batch->scheduled_at, 'D MMM HH:mm')],
        ];

        $steps = [];

        foreach ($definition as $position => [$n, $label, $note]) {
            $steps[] = [
                'n' => $n,
                'label' => $label,
                'state' => match (true) {
                    $index === 3 => 'done',
                    $position < $index => 'done',
                    $position === $index => 'current',
                    default => 'upcoming',
                },
                'note' => $note,
            ];
        }

        return $steps;
    }

    /**
     * Goedkeuringen met naam en tijd.
     *
     * @return Collection<int, array{name: string, at: string, is_me: bool}>
     */
    public function approvals(): Collection
    {
        $approvals = $this->batch->approvals;
        $names = User::query()->whereIn('id', $approvals->pluck('user_id'))->pluck('name', 'id');

        return $approvals->map(fn ($approval) => [
            'name' => (string) ($names[$approval->user_id] ?? 'Onbekend'),
            'at' => (string) DutchTime::format($approval->approved_at, 'D MMM HH:mm'),
            'is_me' => $this->user !== null && (int) $approval->user_id === (int) $this->user->getKey(),
        ])->values();
    }

    public function submitterName(): ?string
    {
        return $this->batch->submitted_by ? User::query()->whereKey($this->batch->submitted_by)->value('name') : null;
    }

    /**
     * De volgende stap in gewone taal, gericht aan de ingelogde medewerker.
     *
     * @return array{title: string, text: string, tone: string}
     */
    public function nextStep(): array
    {
        $batch = $this->batch;
        $isPublisher = $this->user?->hasRole(StaffRole::Publisher->value) ?? false;
        $remaining = max(0, PublicationBatch::REQUIRED_APPROVALS - $this->approvals()->count());
        $submittedByMe = $this->user !== null && (int) $batch->submitted_by === (int) $this->user->getKey();
        $approvedByMe = $this->approvals()->contains('is_me', true);

        return match ($batch->status) {
            BatchStatus::Draft => [
                'title' => $batch->items()->exists() ? 'Klaar om in te dienen' : 'Wacht op uitslagen',
                'text' => $batch->items()->exists()
                    ? 'Controleer de uitslagen hieronder en dien de batch in. Daarna keuren twee andere publicatiemedewerkers goed.'
                    : 'Zodra scorecontrole uitslagen definitief maakt, komen ze in deze batch.',
                'tone' => 'info',
            ],
            BatchStatus::PendingApproval => [
                'title' => "Nog {$remaining} ".($remaining === 1 ? 'goedkeuring' : 'goedkeuringen').' nodig',
                'text' => match (true) {
                    $submittedByMe => 'Je hebt deze batch zelf ingediend; twee collega\'s van Publicatie moeten goedkeuren.',
                    $approvedByMe => 'Je hebt al goedgekeurd; nog een andere collega van Publicatie moet goedkeuren.',
                    $isPublisher => 'Jij kunt goedkeuren: controleer naam, provincie, cijfer en weergave van iedere uitslag.',
                    default => 'Twee publicatiemedewerkers keuren goed, de indiener nooit zelf.',
                },
                'tone' => 'waarschuwing',
            ],
            BatchStatus::Approved => [
                'title' => $batch->isDue() ? 'Gepland moment is verstreken' : 'Goedgekeurd, publiceert automatisch',
                'text' => $batch->isDue()
                    ? 'De batch had al gepubliceerd moeten zijn. Publiceer nu, of controleer de planner.'
                    : 'Het systeem publiceert op '.DutchTime::format($batch->scheduled_at, 'dddd D MMMM [om] HH:mm').'. Eerder publiceren kan met "Nu publiceren".',
                'tone' => $batch->isDue() ? 'fout' : 'succes',
            ],
            BatchStatus::Published => [
                'title' => 'Gepubliceerd',
                'text' => 'Live sinds '.DutchTime::format($batch->published_at, 'dddd D MMMM [om] HH:mm').'. Correcties lopen via een correctiedossier.',
                'tone' => 'succes',
            ],
        };
    }

    /**
     * Wat publicatie blokkeert of aandacht vraagt.
     *
     * @return list<array{text: string, url: string|null, tone: string}>
     */
    public function blockers(): array
    {
        $batch = $this->batch;
        $blockers = [];

        if ($batch->status === BatchStatus::Published) {
            return [];
        }

        $items = $batch->items()->with('entry')->get();

        if ($items->isEmpty()) {
            $blockers[] = ['text' => 'De batch bevat nog geen uitslagen.', 'url' => null, 'tone' => 'neutraal'];
        }

        $withdrawn = $items->filter(fn (PublicationItem $item) => in_array($item->entry?->status, [EntryStatus::Withdrawn, EntryStatus::Cancelled], true))->count();

        if ($withdrawn > 0) {
            $blockers[] = ['text' => "{$withdrawn} ".($withdrawn === 1 ? 'uitslag hoort' : 'uitslagen horen').' bij een ingetrokken of geannuleerde inschrijving.', 'url' => null, 'tone' => 'fout'];
        }

        $objections = Objection::query()
            ->whereIn('entry_id', $items->pluck('entry_id'))
            ->whereIn('status', [ObjectionStatus::Submitted, ObjectionStatus::Reviewing])
            ->count();

        if ($objections > 0) {
            $blockers[] = [
                'text' => "{$objections} ".($objections === 1 ? 'open bezwaar' : 'open bezwaren').' op uitslagen in deze batch.',
                'url' => ObjectionResource::canViewAny() ? ObjectionResource::getUrl('index') : null,
                'tone' => 'waarschuwing',
            ];
        }

        if ($batch->status !== BatchStatus::Approved && $batch->scheduled_at->isPast()) {
            $blockers[] = ['text' => 'Het geplande publicatiemoment is verstreken zonder twee goedkeuringen.', 'url' => null, 'tone' => 'fout'];
        }

        return $blockers;
    }

    /**
     * @return array{total: int, public: int, confidential: int, provinces: list<string>}
     */
    public function counts(): array
    {
        $items = $this->batch->items()->with('province')->get();

        return [
            'total' => $items->count(),
            'public' => $items->where('visibility', ItemVisibility::Public)->count(),
            'confidential' => $items->where('visibility', ItemVisibility::Confidential)->count(),
            'provinces' => $items->map(fn (PublicationItem $item) => $item->province?->name)->filter()->unique()->sort()->values()->all(),
        ];
    }
}
