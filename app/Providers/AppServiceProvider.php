<?php

namespace App\Providers;

use App\Domain\Charities\Listeners\ReserveOnOrderPaid;
use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityPayout;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Listeners\ActivateSponsorPlacements;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Marketing\Listeners\GenerateNationalPressRelease;
use App\Domain\Marketing\Listeners\GeneratePressReleasesOnFreeze;
use App\Domain\Marketing\Listeners\GrantFrozenRecognitions;
use App\Domain\Marketing\Listeners\GrantNationalRecognitions;
use App\Domain\Marketing\Listeners\GrantParticipantRecognition;
use App\Domain\Marketing\Listeners\GrantTestedRecognition;
use App\Domain\Marketing\Listeners\LiftProvinceEmbargo;
use App\Domain\Marketing\Listeners\PublishProvincePressRelease;
use App\Domain\Marketing\Models\NewsPost;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Events\EntryRegistered;
use App\Domain\Participants\Events\ObjectionSubmitted;
use App\Domain\Participants\Events\ProfileSubmitted;
use App\Domain\Participants\Listeners\SendRegistrationConfirmation;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\Location;
use App\Domain\Participants\Models\OpeningHour;
use App\Domain\Participants\Models\OpeningHourException;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Models\Profile;
use App\Domain\Platform\Listeners\NotifyStaff;
use App\Domain\Ranking\Events\BatchApproved;
use App\Domain\Ranking\Events\BatchPublished;
use App\Domain\Ranking\Events\BatchSubmitted;
use App\Domain\Ranking\Events\CorrectionCaseOpened;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Events\ProvinceRevealed;
use App\Domain\Ranking\Listeners\InviteFinalists;
use App\Domain\Ranking\Listeners\LinkFinalizedResult;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Testing\Contracts\ExclusionSource;
use App\Domain\Testing\Events\ResultFinalized;
use App\Domain\Vault\Services\VaultExclusionSource;
use App\Domain\Vault\Services\VaultService;
use App\Domain\Vouchers\Listeners\CreateCampaignsOnFreeze;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Support\Cdn\CdnPurger;
use App\Support\Cdn\CloudflareCdnPurger;
use App\Support\Cdn\NullCdnPurger;
use App\Support\Database\FreshSecondaryConnections;
use App\Support\DesignTokens;
use App\Support\Pdok\PdokLocatieserver;
use App\Support\PublicCache;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DesignTokens::class);

        $this->app->singleton(PdokLocatieserver::class, fn () => new PdokLocatieserver(
            rtrim((string) config('services.pdok.base_url', 'https://api.pdok.nl/bzk/locatieserver/search/v3_1'), '/'),
        ));

        // De kluis is één instantie per request, zodat de systeemcontext (jobs) consistent is.
        $this->app->singleton(VaultService::class);

        // De testketen kent geen identiteit; uitsluitingen komen via de kluis binnen als testnummers.
        $this->app->bind(ExclusionSource::class, VaultExclusionSource::class);

        // CDN-invalidatie voor de paginacache: geen CDN tot er een voor de site staat.
        $this->app->singleton(CdnPurger::class, function (): CdnPurger {
            $cloudflare = config('publiccache.cdn.cloudflare', []);

            return config('publiccache.cdn.driver') === 'cloudflare' && filled($cloudflare['zone_id'] ?? null) && filled($cloudflare['api_token'] ?? null)
                ? new CloudflareCdnPurger((string) $cloudflare['zone_id'], (string) $cloudflare['api_token'])
                : new NullCdnPurger;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        Model::shouldBeStrict(! $this->app->isProduction());

        // Modellen leven per domein in app/Domain/<Domein>/Models; factories blijven plat in database/factories.
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            return 'Database\\Factories\\'.Str::afterLast($modelName, '\\').'Factory';
        });

        // De testketen en de kluis hebben eigen migraties op eigen connecties.
        $this->loadMigrationsFrom([
            database_path('migrations/testing'),
            database_path('migrations/vault'),
        ]);

        // `migrate:fresh` maakt ook de testketen- en kluisdatabase leeg (na de productiebevestiging).
        Event::listen(CommandStarting::class, [FreshSecondaryConnections::class, 'commandStarting']);
        Event::listen(MigrationsStarted::class, [FreshSecondaryConnections::class, 'migrationsStarted']);

        // Domein-events (listeners leven per domein, buiten app/Listeners).
        Event::listen(EntryRegistered::class, SendRegistrationConfirmation::class);
        Event::listen(EntryRegistered::class, GrantParticipantRecognition::class);
        Event::listen(ResultFinalized::class, LinkFinalizedResult::class);
        Event::listen(BatchPublished::class, GrantTestedRecognition::class);
        Event::listen(BatchPublished::class, GrantNationalRecognitions::class);
        Event::listen(EditionFrozen::class, GrantFrozenRecognitions::class);
        Event::listen(EditionFrozen::class, InviteFinalists::class);
        Event::listen(EditionFrozen::class, CreateCampaignsOnFreeze::class);
        Event::listen(EditionFrozen::class, GeneratePressReleasesOnFreeze::class);
        Event::listen(BatchPublished::class, GenerateNationalPressRelease::class);
        Event::listen(ProvinceRevealed::class, PublishProvincePressRelease::class);
        Event::listen(OrderPaid::class, ActivateSponsorPlacements::class);
        Event::listen(OrderPaid::class, ReserveOnOrderPaid::class);
        Event::listen(ProvinceRevealed::class, LiftProvinceEmbargo::class);

        // Meldingen in de bel van de backoffice, per rol (App\Domain\Platform\Services\StaffNotifier).
        Event::listen(BatchSubmitted::class, [NotifyStaff::class, 'onBatchSubmitted']);
        Event::listen(BatchApproved::class, [NotifyStaff::class, 'onBatchApproved']);
        Event::listen(BatchPublished::class, [NotifyStaff::class, 'onBatchPublished']);
        Event::listen(ObjectionSubmitted::class, [NotifyStaff::class, 'onObjectionSubmitted']);
        Event::listen(CorrectionCaseOpened::class, [NotifyStaff::class, 'onCorrectionCaseOpened']);
        Event::listen(ProfileSubmitted::class, [NotifyStaff::class, 'onProfileSubmitted']);
        Event::listen(OrderPaid::class, [NotifyStaff::class, 'onOrderPaid']);

        // Paginacache: iedere wijziging aan publieke inhoud laat alle gecachte pagina's vervallen;
        // het CDN wordt één keer per request/commando gepurged.
        PublicCache::bumpOn([
            Edition::class, Province::class, TermsVersion::class,
            Entry::class, Company::class, Location::class, OpeningHour::class, OpeningHourException::class, Profile::class,
            PublicationBatch::class, RankingSnapshot::class, Finalist::class,
            NewsPost::class, PressRelease::class, Recognition::class,
            Sponsor::class, Placement::class, SponsorLink::class, Product::class,
            Charity::class, CharityReservation::class, CharityPayout::class,
            VoucherCampaign::class, VoucherWinner::class,
        ]);
        $this->app->terminating(fn () => PublicCache::purgeCdnIfDirty());

        // Wachtwoordherstel voor deelnemers: eigen route en Nederlandse mail.
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => $notifiable instanceof ParticipantUser
            ? route('portaal.wachtwoord.herstellen', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()])
            : url("/admin/password-reset/reset?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset())));
        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => (new MailMessage)
            ->subject('Wachtwoord opnieuw instellen')
            ->greeting("Beste {$notifiable->name},")
            ->line('U ontvangt deze e-mail omdat er een wachtwoordherstel is aangevraagd voor uw account.')
            ->action('Nieuw wachtwoord instellen', ResetPassword::$createUrlCallback ? call_user_func(ResetPassword::$createUrlCallback, $notifiable, $token) : '#')
            ->line('Deze link is 60 minuten geldig. Heeft u dit niet aangevraagd, dan hoeft u niets te doen.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol'));

        // Ongeauthenticeerde deelnemers gaan naar de portaal-login, niet naar de backoffice.
        Authenticate::redirectUsing(fn (Request $request) => match (true) {
            $request->is('portaal*') => route('portaal.inloggen'),
            $request->is('panel*') => route('panel.inloggen'),
            default => route('filament.admin.auth.login'),
        });
        RedirectIfAuthenticated::redirectUsing(fn (Request $request) => match (true) {
            $request->is('portaal*') => route('portaal.dashboard'),
            $request->is('panel*') => route('panel.overzicht'),
            default => '/admin',
        });
    }
}
