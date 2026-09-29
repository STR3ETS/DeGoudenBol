<?php

namespace App\Filament\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Actions\AssignSampleNumber;
use App\Domain\Intake\Actions\ReceiveSample;
use App\Domain\Intake\Data\IntakeData;
use App\Domain\Intake\Services\DeliveryCode;
use App\Domain\Participants\Enums\Allergen;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SampleRound;
use App\Support\DutchTime;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * Stap 3 en 4 uit het kernproces: aanleverbewijs scannen, ontvangst vastleggen (tijd, temperatuur,
 * stuks, foto), versheidsklok starten en direct een testnummer met etiket uitgeven.
 * Dit is de enige plek in de backoffice waar identiteit en testnummer elkaar raken.
 */
class Intake extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Testdagen';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Ontvangst & registratie';

    protected static ?string $title = 'Ontvangst & registratie';

    protected static ?string $slug = 'ontvangst';

    protected string $view = 'filament.pages.intake';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $entry = null;

    /** @var array<string, mixed>|null */
    public ?array $lastSample = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(StaffRole::Intake->value) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'round' => SampleRound::Provincial->value,
            'piece_count' => Edition::current()?->settings->piecesPerEntry ?? 8,
            'assign_number' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Aanlevercode (QR)')
                    ->placeholder('XXXX-XXXX')
                    ->autofocus()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn () => $this->lookup())
                    ->extraInputAttributes(['data-intake-code' => 'true', 'autocomplete' => 'off', 'autocapitalize' => 'characters']),
                Select::make('round')
                    ->label('Ronde')
                    ->options(SampleRound::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->lookup())
                    ->helperText('Beslisronde en finale: nieuwe monsters (B01…, F01…) van al gepubliceerde deelnemers.'),
                TextInput::make('temperature_c')->label('Temperatuur (°C)')->numeric()->step(0.1),
                TextInput::make('piece_count')->label('Aantal stuks')->numeric()->integer()->minValue(1)->required(),
                FileUpload::make('photo')->label('Foto van het monster')->image()->disk('local')->directory('intakes')->visibility('private')->columnSpanFull(),
                Textarea::make('notes')->label('Opmerkingen')->rows(2)->columnSpanFull(),
                Toggle::make('assign_number')->label('Direct testnummer toekennen en etiket klaarzetten')->columnSpanFull(),
            ]);
    }

    public function lookup(): void
    {
        $this->entry = null;
        $code = app(DeliveryCode::class)->normalize((string) ($this->data['code'] ?? ''));

        if ($code === null) {
            return;
        }

        $entry = Entry::query()->where('delivery_code', $code)->with(['province', 'deliverySlot.testLocation', 'edition', 'company'])->first();

        if ($entry === null) {
            Notification::make()->title('Onbekende aanlevercode')->body("Er is geen inschrijving met code {$code}.")->danger()->send();

            return;
        }

        $receivable = in_array($entry->status, ReceiveSample::receivableStatuses($this->round()), true);

        $this->entry = [
            'ulid' => $entry->ulid,
            'name' => $entry->public_name,
            'company' => $entry->company->name,
            'province' => $entry->province->name,
            'status' => $entry->status->getLabel(),
            'receivable' => $receivable,
            'allergens' => array_map(fn (string $allergen) => Allergen::tryFrom($allergen)?->getLabel() ?? $allergen, $entry->allergens ?? []),
            'slot' => $entry->deliverySlot ? DutchTime::format($entry->deliverySlot->starts_at, 'dd D MMM, HH:mm').' – '.DutchTime::format($entry->deliverySlot->ends_at, 'HH:mm') : null,
            'notes' => $entry->product_notes,
        ];

        if (! $receivable) {
            Notification::make()->title('Deze inschrijving kan nu niet worden ontvangen in de '.strtolower($this->round()->getLabel()))->body('Status: '.$entry->status->getLabel())->warning()->send();
        }
    }

    public function receive(): void
    {
        $state = $this->form->getState();

        if ($this->entry === null) {
            $this->lookup();
        }

        if ($this->entry === null || ! $this->entry['receivable']) {
            Notification::make()->title('Scan of typ eerst een geldige aanlevercode')->warning()->send();

            return;
        }

        $entry = Entry::query()->where('ulid', $this->entry['ulid'])->firstOrFail();
        $round = $this->round();

        try {
            $sample = app(ReceiveSample::class)($entry, new IntakeData(
                pieceCount: (int) $state['piece_count'],
                temperatureC: filled($state['temperature_c'] ?? null) ? (float) $state['temperature_c'] : null,
                photoPath: $state['photo'] ?? null,
                notes: $state['notes'] ?? null,
            ), auth()->user(), $round);

            if ($state['assign_number'] ?? false) {
                $sample = app(AssignSampleNumber::class)($sample, auth()->user());
            }
        } catch (Throwable $exception) {
            Notification::make()->title('Ontvangst niet gelukt')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->lastSample = [
            'number' => $sample->sample_number,
            'ulid' => $sample->ulid,
            'fresh_until' => DutchTime::format($sample->intake->freshness_expires_at, 'HH:mm'),
            'label_url' => $sample->sample_number ? route('etiket', $sample) : null,
        ];

        $this->entry = null;
        $this->form->fill([
            'code' => '',
            'round' => $round->value,
            'piece_count' => Edition::current()?->settings->piecesPerEntry ?? 8,
            'assign_number' => true,
        ]);

        Notification::make()
            ->title($sample->sample_number ? "Monster ontvangen: testnummer {$sample->sample_number}" : 'Monster ontvangen')
            ->body('Versheidsklok loopt tot '.$this->lastSample['fresh_until'].'.')
            ->success()
            ->send();
    }

    private function round(): SampleRound
    {
        return SampleRound::tryFrom((string) ($this->data['round'] ?? '')) ?? SampleRound::Provincial;
    }
}
