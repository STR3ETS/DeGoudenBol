<?php

namespace App\Filament\Pages;

use App\Filament\Support\DashboardData;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Eigen dashboard: begroeting, kerncijfers, acties, recente aanmeldingen, plekken en mijlpalen.
 */
class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Overzicht';

    public function getHeading(): string
    {
        return $this->data()->greeting().' 👋';
    }

    public function getSubheading(): ?string
    {
        return $this->data()->subheading();
    }

    /**
     * Gedeeld met de bel in de topbar en de sidebar-voet (scoped binding in AdminPanelProvider).
     */
    public function data(): DashboardData
    {
        return app(DashboardData::class);
    }

    /**
     * Geen losse widgets: alles staat in de eigen view.
     */
    public function getWidgets(): array
    {
        return [];
    }
}
