<?php

namespace App\Filament\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Models\TestSession;
use App\Filament\Concerns\CoordinatesTestSessions;
use App\Filament\Support\TestDayData;
use App\Models\User;
use App\Support\DutchTime;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Testdag-cockpit: één scherm per dag met leveringen, sessies op een tijdlijn, versheidsklokken
 * en de knoppen van coördinatie (schema genereren, starten, afsluiten) direct in de rij.
 */
class TestDay extends Page
{
    use CoordinatesTestSessions;

    protected string $view = 'filament.pages.testdag';

    protected static ?string $slug = 'testdag';

    protected static ?string $title = 'Testdag';

    protected static ?string $navigationLabel = 'Testdag';

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    #[Url(as: 'dag')]
    public ?string $date = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole([
            StaffRole::Admin->value,
            StaffRole::Intake->value,
            StaffRole::Coordinator->value,
            StaffRole::Reviewer->value,
        ]);
    }

    public function mount(): void
    {
        if ($this->date === null || CarbonImmutable::hasFormat($this->date, 'Y-m-d') === false) {
            $this->date = $this->now()->format('Y-m-d');
        }
    }

    public function getSubheading(): ?string
    {
        $data = $this->data();

        return ucfirst($data->day()->locale('nl')->isoFormat('dddd D MMMM YYYY')).' · week '.$data->day()->isoWeek()
            .($data->isToday() ? ' · vandaag' : '');
    }

    public function data(): TestDayData
    {
        $day = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->date, DutchTime::zone())->startOfDay();

        return new TestDayData(Edition::current(), $day, $this->now());
    }

    public function previousDay(): void
    {
        $this->date = $this->data()->day()->subDay()->format('Y-m-d');
    }

    public function nextDay(): void
    {
        $this->date = $this->data()->day()->addDay()->format('Y-m-d');
    }

    public function today(): void
    {
        $this->date = $this->now()->format('Y-m-d');
    }

    public function goTo(string $date): void
    {
        if (CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            $this->date = $date;
        }
    }

    public function generateScheduleAction(): Action
    {
        return Action::make('generateSchedule')
            ->label('Uitserveerschema genereren')
            ->requiresConfirmation()
            ->modalHeading('Uitserveerschema genereren')
            ->modalDescription('Een systeemproces met kluistoegang sluit panelleden uit bij belangenconflicten en allergenen. Het schema toont alleen testnummers en panelcodes.')
            ->action(function (array $arguments): void {
                $session = $this->session($arguments);

                if ($this->canGenerateScheduleFor($session)) {
                    $this->generateScheduleFor($session);
                }
            });
    }

    public function startSessionAction(): Action
    {
        return Action::make('startSession')
            ->label('Sessie starten')
            ->action(function (array $arguments): void {
                $session = $this->session($arguments);

                if ($this->canStart($session)) {
                    $this->startSession($session);
                }
            });
    }

    public function closeSessionAction(): Action
    {
        return Action::make('closeSession')
            ->label('Sessie afsluiten')
            ->requiresConfirmation()
            ->modalDescription('Na het afsluiten kunnen panelleden geen kaarten meer indienen voor deze sessie.')
            ->action(function (array $arguments): void {
                $session = $this->session($arguments);

                if ($this->canClose($session)) {
                    $this->closeSession($session);
                }
            });
    }

    /**
     * Welke knop een sessie in de cockpit krijgt: de eerstvolgende stap van coördinatie.
     *
     * @return array{action: string, label: string}|null
     */
    public function nextStepFor(TestSession $session): ?array
    {
        return match (true) {
            $this->canStart($session) => ['action' => 'startSession', 'label' => 'Starten'],
            $this->canClose($session) => ['action' => 'closeSession', 'label' => 'Afsluiten'],
            $this->canGenerateScheduleFor($session) && $session->schedule_generated_at === null => ['action' => 'generateSchedule', 'label' => 'Schema genereren'],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function session(array $arguments): TestSession
    {
        return TestSession::query()->whereKey($arguments['session'] ?? null)->firstOrFail();
    }

    private function now(): CarbonImmutable
    {
        return DutchTime::display(now());
    }
}
