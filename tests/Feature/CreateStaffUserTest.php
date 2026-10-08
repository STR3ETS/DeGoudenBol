<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStaffUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_first_administrator_can_be_created_from_the_command_line_without_seeders(): void
    {
        $this->artisan('staff:create', ['email' => 'Beheer@Example.test', 'name' => 'Eerste Beheerder', '--password' => 'geheim-wachtwoord'])
            ->expectsOutputToContain('Account aangemaakt: beheer@example.test met rol Beheerder.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'beheer@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole(StaffRole::Admin->value));
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));

        $this->artisan('staff:create', ['email' => 'beheer@example.test', 'name' => 'Eerste Beheerder', '--role' => 'finance'])
            ->expectsOutputToContain('Bestaand account bijgewerkt')
            ->assertSuccessful();
        $this->assertTrue($user->fresh()->hasRole(StaffRole::Finance->value));

        $this->artisan('staff:create', ['email' => 'panel@example.test', 'name' => 'Panel', '--role' => 'panelist'])->assertFailed();
        $this->artisan('staff:create', ['email' => 'geen-adres', 'name' => 'X'])->assertFailed();
    }
}
