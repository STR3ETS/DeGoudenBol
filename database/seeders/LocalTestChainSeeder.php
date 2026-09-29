<?php

namespace Database\Seeders;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TestLocation;
use App\Domain\Intake\Actions\AssignSampleNumber;
use App\Domain\Intake\Actions\ReceiveSample;
use App\Domain\Intake\Actions\ScheduleDelivery;
use App\Domain\Intake\Data\IntakeData;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Actions\GenerateServingSchedule;
use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Alleen lokaal: aanleverslots, een panelpool van zes demo-panelleden en één lopende sessie met de
 * betaalde [DEMO]-inschrijvingen als genummerde monsters, zodat panel-app, scorecontrole en
 * publicatie iets te tonen hebben. Idempotent: draait alleen als er nog geen monsters zijn.
 */
class LocalTestChainSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || Sample::query()->exists()) {
            return;
        }

        $edition = Edition::query()->where('year', 2026)->firstOrFail();
        $location = TestLocation::query()->where('is_active', true)->first();

        foreach ([0, 1, 2] as $dayOffset) {
            foreach (['09:00', '11:00', '14:00'] as $time) {
                $startsAt = now()->addDays($dayOffset + 1)->setTimeFromTimeString($time);

                DeliverySlot::query()->firstOrCreate(
                    ['edition_id' => $edition->getKey(), 'starts_at' => $startsAt],
                    ['test_location_id' => $location?->getKey(), 'ends_at' => $startsAt->addHour(), 'capacity' => 8],
                );
            }
        }

        $panelists = [];

        for ($i = 1; $i <= 6; $i++) {
            $user = User::query()->firstOrCreate(
                ['email' => "panellid{$i}@degoudenbol.test"],
                ['name' => "[DEMO] Panellid {$i}", 'password' => 'password', 'is_active' => true],
            );

            if (! $user->hasRole(StaffRole::Panelist->value)) {
                $user->assignRole(StaffRole::Panelist->value);
            }

            $panelists[] = Panelist::query()->firstOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'edition_id' => $edition->getKey(),
                    'display_code' => sprintf('P%02d', $i),
                    'pool_status' => PoolStatus::Active,
                    'fee_per_session_cents' => 7500,
                    'allergens' => $i === 6 ? ['noten'] : [],
                    'consent_at' => now(),
                ],
            );
        }

        $intake = User::query()->where('email', 'intake@degoudenbol.test')->first();
        $slot = DeliverySlot::query()->where('edition_id', $edition->getKey())->orderBy('starts_at')->first();

        $entries = Entry::query()
            ->where('edition_id', $edition->getKey())
            ->whereIn('status', [EntryStatus::Registered, EntryStatus::Scheduled])
            ->whereHas('company', fn ($query) => $query->where('name', 'like', '[DEMO]%'))
            ->get();

        if ($entries->isEmpty() || $intake === null) {
            return;
        }

        $session = TestSession::query()->create([
            'edition_id' => $edition->getKey(),
            'test_location_id' => $location?->getKey(),
            'round' => SampleRound::Provincial,
            'name' => '[DEMO] Sessie A',
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addHours(2),
            'status' => SessionStatus::Running,
            'max_samples' => $edition->settings->maxSamplesPerSession,
        ]);

        Auth::guard('web')->login($intake);

        try {
            foreach ($entries as $index => $entry) {
                app(ScheduleDelivery::class)($entry, $slot);
                $sample = app(ReceiveSample::class)($entry->refresh(), new IntakeData(pieceCount: 8, temperatureC: 21.0 + $index), $intake);
                $sample = app(AssignSampleNumber::class)($sample, $intake);
                $session->samples()->attach($sample->getKey(), ['serving_order' => $index + 1]);
            }
        } finally {
            Auth::guard('web')->logout();
        }

        $session->panelists()->attach(collect($panelists)->map->getKey()->all());

        app(GenerateServingSchedule::class)($session);
    }
}
