<?php

namespace App\Livewire\Portal;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Location;
use App\Domain\Participants\Models\OpeningHour;
use App\Domain\Participants\Models\Profile;
use App\Domain\Participants\Services\CompanyAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Openbaar profiel, bedrijfsgegevens, standplaats en openingstijden.
 * Tekstwijzigingen gaan als concept naar Communicatie; de site toont de goedgekeurde versie.
 */
#[Layout('components.layouts.portal')]
#[Title('Profiel')]
class ProfileEditor extends Component
{
    public Company $company;

    public string $tagline = '';

    public string $story = '';

    public string $specialtiesText = '';

    public string $website = '';

    public string $instagram = '';

    public string $facebook = '';

    public string $street = '';

    public string $houseNumber = '';

    public string $postcode = '';

    public string $city = '';

    public ?string $seasonFrom = null;

    public ?string $seasonTo = null;

    /** @var array<int, array{opens: string, closes: string, closed: bool}> */
    public array $hours = [];

    public function mount(?string $bedrijf = null): void
    {
        $this->company = app(CompanyAccess::class)->resolve(
            auth('participant')->user(),
            $bedrijf,
            [CompanyUserRole::Owner, CompanyUserRole::Staff],
        );

        $profile = $this->profile();
        $location = $this->location();

        $this->tagline = (string) ($profile->tagline ?? '');
        $this->story = (string) ($profile->story ?? '');
        $this->specialtiesText = implode(', ', $profile->specialties ?? []);
        $this->website = (string) ($this->company->website ?? '');
        $this->instagram = (string) ($this->company->socials['instagram'] ?? '');
        $this->facebook = (string) ($this->company->socials['facebook'] ?? '');

        if ($location !== null) {
            $this->street = $location->street;
            $this->houseNumber = $location->house_number;
            $this->postcode = $location->postcode;
            $this->city = $location->city;
            $this->seasonFrom = $location->season_from?->toDateString();
            $this->seasonTo = $location->season_to?->toDateString();
        }

        $existing = $location?->openingHours->keyBy('weekday') ?? collect();

        foreach (array_keys(OpeningHour::WEEKDAYS) as $weekday) {
            $hour = $existing->get($weekday);

            $this->hours[$weekday] = [
                'opens' => $hour?->opens_at ? substr($hour->opens_at, 0, 5) : '',
                'closes' => $hour?->closes_at ? substr($hour->closes_at, 0, 5) : '',
                'closed' => $hour?->is_closed ?? ($hour === null),
            ];
        }
    }

    public function save(): void
    {
        $this->persist();

        session()->flash('status', 'Opgeslagen als concept. Dien het profiel in om het te laten beoordelen.');
    }

    public function submitForReview(): void
    {
        $this->persist();
        $this->profile()->submitForReview();

        session()->flash('status', 'Ingediend. Communicatie beoordeelt de tekst; tot die tijd blijft de laatst goedgekeurde versie zichtbaar.');
    }

    public function render(): View
    {
        return view('livewire.portal.profile-editor', [
            'profile' => $this->profile(),
            'weekdays' => OpeningHour::WEEKDAYS,
        ]);
    }

    private function persist(): void
    {
        $this->validate([
            'tagline' => ['nullable', 'string', 'max:140'],
            'story' => ['nullable', 'string', 'max:3000'],
            'specialtiesText' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:190'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:190'],
            'street' => ['required', 'string', 'max:120'],
            'houseNumber' => ['required', 'string', 'max:16'],
            'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'city' => ['required', 'string', 'max:120'],
            'seasonFrom' => ['nullable', 'date'],
            'seasonTo' => ['nullable', 'date', 'after_or_equal:seasonFrom'],
            'hours.*.opens' => ['nullable', 'date_format:H:i'],
            'hours.*.closes' => ['nullable', 'date_format:H:i'],
            'hours.*.closed' => ['boolean'],
        ], [], [
            'tagline' => 'korte omschrijving',
            'story' => 'verhaal',
            'specialtiesText' => 'specialiteiten',
            'website' => 'website',
            'street' => 'straat',
            'houseNumber' => 'huisnummer',
            'postcode' => 'postcode',
            'city' => 'plaats',
            'seasonFrom' => 'seizoen van',
            'seasonTo' => 'seizoen tot',
        ]);

        DB::transaction(function (): void {
            $profile = $this->profile();
            $textChanged = $profile->tagline !== ($this->tagline ?: null)
                || $profile->story !== ($this->story ?: null)
                || ($profile->specialties ?? []) !== $this->specialties();

            $profile->fill([
                'tagline' => $this->tagline ?: null,
                'story' => $this->story ?: null,
                'specialties' => $this->specialties(),
            ]);

            if ($textChanged && $profile->moderation_status !== ModerationStatus::Pending) {
                $profile->moderation_status = ModerationStatus::Draft;
            }

            $profile->save();

            $this->company->update([
                'website' => $this->website ?: null,
                'socials' => array_filter([
                    'instagram' => ltrim(trim($this->instagram), '@') ?: null,
                    'facebook' => trim($this->facebook) ?: null,
                ]),
            ]);

            $location = $this->location() ?? new Location(['company_id' => $this->company->getKey(), 'is_primary' => true]);
            $location->fill([
                'street' => trim($this->street),
                'house_number' => trim($this->houseNumber),
                'postcode' => strtoupper(trim($this->postcode)),
                'city' => trim($this->city),
                'season_from' => $this->seasonFrom ?: null,
                'season_to' => $this->seasonTo ?: null,
            ]);

            if ($location->province_id === null) {
                $location->province_id = $this->company->entries()->latest('id')->value('province_id');
            }

            $location->save();

            foreach ($this->hours as $weekday => $hour) {
                $closed = (bool) ($hour['closed'] ?? false) || blank($hour['opens']) || blank($hour['closes']);

                $location->openingHours()->updateOrCreate(
                    ['weekday' => (int) $weekday],
                    [
                        'opens_at' => $closed ? null : $this->normalizeTime($hour['opens']),
                        'closes_at' => $closed ? null : $this->normalizeTime($hour['closes']),
                        'is_closed' => $closed,
                    ],
                );
            }
        });

        $this->company->refresh();
    }

    /**
     * Zelfde opslagformaat op elke database: 10:00 wordt 10:00:00.
     */
    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }

    /**
     * @return list<string>
     */
    private function specialties(): array
    {
        return collect(explode(',', $this->specialtiesText))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function profile(): Profile
    {
        $profile = $this->company->profile;

        if ($profile === null) {
            $profile = $this->company->profile()->create(['specialties' => []])->refresh();
            $this->company->setRelation('profile', $profile);
        }

        return $profile;
    }

    private function location(): ?Location
    {
        return $this->company->primaryLocation()->with('openingHours')->first();
    }
}
