<?php

namespace App\Providers\Filament;

use App\Domain\Participants\Services\ProvinceCapacity;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Search\CommandPaletteSearchProvider;
use App\Filament\Support\DashboardData;
use App\Models\User;
use App\Support\DesignTokens;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Menugroepen op werk, in de volgorde van het seizoen (docs/02 §7). Instellingen zit in de voet van de sidebar.
     *
     * @var list<string>
     */
    public const array NAVIGATION_GROUPS = [
        'Bakkers',
        'Testdagen',
        'Uitslag',
        'Campagne',
        'Geld',
    ];

    /**
     * Rollen die een groep altijd open willen hebben, naast de groep van de huidige fase.
     *
     * @var array<string, list<StaffRole>>
     */
    private const array GROUP_ROLES = [
        'Bakkers' => [StaffRole::Intake, StaffRole::Communication],
        'Testdagen' => [StaffRole::Intake, StaffRole::Coordinator, StaffRole::Reviewer],
        'Uitslag' => [StaffRole::Publisher, StaffRole::Reviewer],
        'Campagne' => [StaffRole::Communication, StaffRole::VoucherManager],
        'Geld' => [StaffRole::Finance],
    ];

    public function boot(): void
    {
        FilamentTimezone::set((string) config('app.display_timezone'));

        // Rij-acties in tabellen altijd als icoonknop met het label als tooltip: tekstlinks nemen te veel
        // breedte in en geven horizontale scrollbalken. Gegroepeerde acties (dropdown) blijven zoals ze zijn.
        // Filters staan als losse keuzelijsten in de toolbar naast het zoekveld en werken direct
        // (geen "toepassen"-knop); de opmaak zit in theme.css.
        Table::configureUsing(fn (Table $table) => $table
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->modifyUngroupedRecordActionsUsing(
                fn (Action $action) => $action
                    ->iconButton()
                    ->tooltip(fn (Action $action): ?string => $action->getLabel()),
            ));

        // Eén berekening per verzoek: het dashboard, de bel in de topbar en de sidebar-voet delen dezelfde cijfers.
        $this->app->scoped(DashboardData::class, fn (Application $app): DashboardData => new DashboardData(
            $app->make('auth')->user(),
            $app->make(ProvinceCapacity::class),
        ));
    }

    public function panel(Panel $panel): Panel
    {
        $tokens = app(DesignTokens::class);

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            ->profile()
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->regenerableRecoveryCodes()],
                isRequired: true,
            )
            ->brandName('De Gouden Bol')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/favicon.svg'))
            ->colors([
                'primary' => Color::hex($tokens->color('goud')),
                'gray' => $tokens->grayPalette(),
                'success' => Color::hex($tokens->color('status.succes.text')),
                'warning' => Color::hex($tokens->color('status.waarschuwing.text')),
                'danger' => Color::hex($tokens->color('status.fout.text')),
                'info' => Color::hex($tokens->color('status.info.text')),
            ])
            ->font('Manrope', url: asset('css/fonts.css'), provider: LocalFontProvider::class)
            ->darkMode(false)
            ->maxContentWidth(Width::Full)
            ->spa(hasPrefetching: true)
            ->globalSearch(CommandPaletteSearchProvider::class)
            ->globalSearchKeyBindings(['mod+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.head.navigatie'))
            ->renderHook(PanelsRenderHook::BODY_END, fn (): View => view('filament.laadpil'))
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn (): View => view('filament.topbar.bel'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('filament.sidebar.voet'))
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('5rem')
            ->navigationGroups(array_map(
                fn (string $group): NavigationGroup => NavigationGroup::make($group)
                    ->collapsed(fn (): bool => ! in_array($group, $this->openGroups(), true)),
                self::NAVIGATION_GROUPS,
            ))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Groepen die bij het eerste bezoek open staan: die van de huidige fase plus die van de eigen rollen.
     * Daarna onthoudt de browser wat de medewerker zelf open- of dichtklapt.
     *
     * @return list<string>
     */
    private function openGroups(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return self::NAVIGATION_GROUPS;
        }

        $groups = [app(DashboardData::class)->season()->menuGroup()];

        foreach (self::GROUP_ROLES as $group => $roles) {
            if ($user->hasAnyRole(array_map(fn (StaffRole $role) => $role->value, $roles))) {
                $groups[] = $group;
            }
        }

        return array_values(array_unique($groups));
    }
}
