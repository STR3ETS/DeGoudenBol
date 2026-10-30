<?php

use App\Http\Controllers\HelpDocsController;
use App\Http\Controllers\Panel\ConflictController as PanelConflictController;
use App\Http\Controllers\Panel\LoginController as PanelLoginController;
use App\Http\Controllers\Panel\OverviewController as PanelOverviewController;
use App\Http\Controllers\Panel\ScorecardController as PanelScorecardController;
use App\Http\Controllers\Panel\SyncController as PanelSyncController;
use App\Http\Controllers\Portal\CampaignController;
use App\Http\Controllers\Portal\CharityController as PortalCharityController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\LoginController;
use App\Http\Controllers\Portal\MagicLinkController;
use App\Http\Controllers\Portal\MarketingController;
use App\Http\Controllers\Portal\PasswordController;
use App\Http\Controllers\Portal\PickupController;
use App\Http\Controllers\Portal\PlanningController;
use App\Http\Controllers\Portal\RegistrationController;
use App\Http\Controllers\Portal\ReportController;
use App\Http\Controllers\Portal\ResultController;
use App\Http\Controllers\Portal\ScanController;
use App\Http\Controllers\Portal\SponsorLinkController;
use App\Http\Controllers\Portal\StaffController;
use App\Http\Controllers\Public\BakkerController;
use App\Http\Controllers\Public\EditionArchiveController;
use App\Http\Controllers\Public\FakeCheckoutController;
use App\Http\Controllers\Public\FinaleController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LiveDataController;
use App\Http\Controllers\Public\ContentController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PressController;
use App\Http\Controllers\Public\ProvinceController;
use App\Http\Controllers\Public\RecognitionController;
use App\Http\Controllers\Public\RegistrationStatusController;
use App\Http\Controllers\Public\SiteController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\SponsorPortalController;
use App\Http\Controllers\Public\SponsorRegistrationController;
use App\Http\Controllers\Public\VoucherController;
use App\Http\Controllers\RedesignController;
use App\Http\Controllers\Staff\LabelController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Webhooks\MollieWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Middleware\EnsureSponsorSession;
use App\Livewire\Portal\ProfileEditor;
use App\Livewire\Public\RegistrationWizard;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

// Publiekssite
// De pagina's komen uit de site-export van Eazyonline (tools/site-import.mjs); de URL's zijn die van de export.
// Routenamen van vóór de import blijven bestaan, zodat route() in de hele app blijft werken.
Route::get('/', HomeController::class)->name('home');
Route::get('/live.json', LiveDataController::class)->name('live');
Route::get('/provincies', [ProvinceController::class, 'index'])->name('provincies.index');
Route::get('/provincies/{province}', [ProvinceController::class, 'show'])->name('provincies.show');
Route::get('/deelnemers', [BakkerController::class, 'index'])->name('bakkers.index');
Route::get('/deelnemers/{company}', [BakkerController::class, 'show'])->name('bakkers.toon');
Route::get('/deelnemen', [SiteController::class, 'deelnemen'])->name('deelnemen');
Route::get('/test', fn () => app(SiteController::class)->page('test'))->name('hoe-werkt-de-test');
Route::get('/faq', fn () => app(SiteController::class)->page('faq'))->name('faq');
Route::get('/ondernemers', fn () => app(SiteController::class)->page('ondernemers'))->name('ondernemers');
Route::get('/sponsoring', fn () => app(SiteController::class)->page('sponsoring'))->name('sponsoren');
Route::get('/finale', FinaleController::class)->name('finale');
Route::get('/cadeaubonnen', fn () => app(SiteController::class)->page('cadeaubonnen'))->name('cadeaubonnen');
Route::get('/goede-doelen', fn () => app(SiteController::class)->page('goede-doelen'))->name('goede-doelen');
Route::get('/bakkers-academie', fn () => app(SiteController::class)->page('bakkers-academie'))->name('bakkers-academie');
Route::get('/over', fn () => app(SiteController::class)->page('over'))->name('over');
Route::get('/privacy', fn () => app(SiteController::class)->page('privacy'))->name('privacy');
Route::get('/voorwaarden', fn () => app(SiteController::class)->page('voorwaarden'))->name('voorwaarden');
Route::get('/voorwaarden/deelname', fn () => app(SiteController::class)->page('voorwaarden-deelname'))->name('voorwaarden.deelname');
Route::get('/voorwaarden/cadeaubonnen', fn () => app(SiteController::class)->page('voorwaarden-cadeaubonnen'))->name('actievoorwaarden');
Route::get('/voorwaarden/{onderdeel}', [SiteController::class, 'voorwaarden'])->where('onderdeel', 'testreglement|klachten|merkgebruik|sponsoring|goede-doelen')->name('voorwaarden.onderdeel');
Route::get('/erkenning', [RecognitionController::class, 'lookup'])->name('erkenning.zoek');
Route::get('/erkenning/{recognition}', [RecognitionController::class, 'show'])->name('erkenning.toon');
Route::get('/erkenning/{recognition}/badge.svg', [RecognitionController::class, 'badge'])->name('erkenning.badge');
Route::get('/edities', [EditionArchiveController::class, 'index'])->name('edities.index');
Route::get('/editie/{edition}', [EditionArchiveController::class, 'show'])->name('edities.show');
Route::get('/nieuws/{post}', [NewsController::class, 'show'])->name('nieuws.toon');
Route::get('/blog', [ContentController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [ContentController::class, 'artikel'])->where('slug', '[a-z0-9-]+')->name('blog.toon');
Route::get('/winnaars', [VoucherController::class, 'winners'])->name('winnaars');

// Oude adressen van vóór de site-import blijven werken.
Route::redirect('/bakkers', '/deelnemers', 301);
Route::get('/bakker/{company}', fn (string $company) => redirect("/deelnemers/{$company}", 301));
Route::get('/provincie/{province}', fn (string $province) => redirect("/provincies/{$province}", 301));
Route::redirect('/hoe-werkt-de-test', '/test', 301);
Route::get('/het-panel', fn () => redirect('/test#organisatie', 301))->name('panel');
Route::get('/nieuws', fn () => redirect('/pers#nieuws', 301))->name('nieuws.index');
Route::redirect('/sponsoren', '/sponsoring', 301);
Route::redirect('/actievoorwaarden', '/voorwaarden/cadeaubonnen', 301);
Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/cadeaubon/claim/{token}', [VoucherController::class, 'claim'])->name('cadeaubon.claim');
    Route::post('/cadeaubon/claim/{token}', [VoucherController::class, 'store'])->name('cadeaubon.claim.bevestigen');
    Route::get('/bon/{token}', [VoucherController::class, 'show'])->name('bon.toon');
});
Route::get('/pers', [PressController::class, 'index'])->name('pers');
Route::get('/pers/kit/{pressRelease}', [PressController::class, 'kit'])->middleware('signed')->name('pers.kit');
Route::get('/pers/{pressRelease}', [PressController::class, 'show'])->name('pers.toon');
Route::post('/nieuwsbrief', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('nieuwsbrief.aanmelden')
    ->withoutMiddleware(PreventRequestForgery::class); // formulier staat op gecachte pagina's; honeypot + throttle + dubbele opt-in
Route::get('/nieuwsbrief/bevestigen/{subscription}', [NewsletterController::class, 'confirm'])->middleware('signed')->name('nieuwsbrief.bevestigen');
Route::get('/nieuwsbrief/afmelden/{subscription}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('nieuwsbrief.afmelden');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/stijlgids', StyleguideController::class)->name('stijlgids');
Route::get('/docs', [HelpDocsController::class, 'index'])->name('docs');
Route::get('/docs.json', [HelpDocsController::class, 'json'])->name('docs.json');
Route::get('/redesign-dashboard', [RedesignController::class, 'dashboard'])->name('redesign.dashboard');
Route::get('/redesign-dashboard-klant', [RedesignController::class, 'klant'])->name('redesign.klant');

// Aanmelden
Route::get('/aanmelden', RegistrationWizard::class)->name('aanmelden');
Route::get('/aanmelden/status/{order}', [RegistrationStatusController::class, 'show'])->name('aanmelden.status');
Route::post('/aanmelden/status/{order}/opnieuw', [RegistrationStatusController::class, 'retry'])->name('aanmelden.opnieuw');
Route::post('/aanmelden/status/{order}/standaardprijs', [RegistrationStatusController::class, 'payStandard'])->name('aanmelden.standaardprijs');

// Sponsor worden (briefing 29 okt §8) en de sponsoromgeving via een persoonlijke beheerlink (§12)
Route::get('/sponsoring/aanmelden', [SponsorRegistrationController::class, 'create'])->name('sponsoring.aanmelden');
Route::post('/sponsoring/aanmelden', [SponsorRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('sponsoring.aanmelden.verwerken');
Route::get('/sponsoring/status/{order}', [SponsorRegistrationController::class, 'status'])->name('sponsoring.status');
Route::get('/sponsoring/bedankt/{sponsor}', [SponsorRegistrationController::class, 'thanks'])->name('sponsoring.bedankt');
Route::get('/sponsoring/beheer', [SponsorPortalController::class, 'requestLink'])->name('sponsoring.beheer');
Route::post('/sponsoring/beheer', [SponsorPortalController::class, 'sendLink'])->middleware('throttle:5,1')->name('sponsoring.beheer.versturen');
Route::get('/sponsor/toegang/{sponsor}', [SponsorPortalController::class, 'access'])->middleware('signed')->name('sponsor.toegang');
Route::prefix('sponsor')->name('sponsor.')->middleware(EnsureSponsorSession::class)->group(function (): void {
    Route::get('/', [SponsorPortalController::class, 'dashboard'])->name('dashboard');
    Route::post('/gegevens', [SponsorPortalController::class, 'updateProfile'])->name('gegevens');
    Route::post('/vermelding', [SponsorPortalController::class, 'addLink'])->middleware('throttle:10,1')->name('vermelding');
    Route::post('/betalen/{order}', [SponsorPortalController::class, 'pay'])->name('betalen');
    Route::get('/factuur/{invoice}', [SponsorPortalController::class, 'invoice'])->name('factuur');
    Route::post('/actie', [SponsorPortalController::class, 'storeOffer'])->middleware('throttle:10,1')->name('actie');
    Route::post('/goed-doel', [SponsorPortalController::class, 'nominateCharity'])->middleware('throttle:10,1')->name('goed-doel');
    Route::post('/uitloggen', [SponsorPortalController::class, 'logout'])->name('uitloggen');
});

// Betalingen
Route::get('/betaling/test/{payment}', [FakeCheckoutController::class, 'show'])->name('betaling.fake');
Route::post('/betaling/test/{payment}', [FakeCheckoutController::class, 'settle'])->name('betaling.fake.afronden');
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->name('webhooks.stripe')
    ->withoutMiddleware(PreventRequestForgery::class);
Route::post('/webhooks/mollie', MollieWebhookController::class)
    ->name('webhooks.mollie')
    ->withoutMiddleware(PreventRequestForgery::class);

// Etiket voor de labelprinter (Ontvangst & registratie)
Route::get('/etiket/{sample}', LabelController::class)->middleware('auth')->name('etiket');

// Panel-app (tablet-PWA voor panelleden; alleen testnummers)
Route::prefix('panel')->name('panel.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/inloggen', [PanelLoginController::class, 'show'])->name('inloggen');
        Route::post('/inloggen', [PanelLoginController::class, 'login'])->middleware('throttle:10,1')->name('inloggen.verwerken');
    });

    Route::middleware(['auth', 'panelist'])->group(function (): void {
        Route::get('/', PanelOverviewController::class)->name('overzicht');
        Route::post('/uitloggen', [PanelLoginController::class, 'logout'])->name('uitloggen');
        Route::get('/monster/{assignment}', [PanelScorecardController::class, 'show'])->name('monster');
        Route::post('/monster/{assignment}', [PanelScorecardController::class, 'store'])->name('monster.indienen');
        Route::get('/conflicten', [PanelConflictController::class, 'index'])->name('conflicten');
        Route::post('/conflicten', [PanelConflictController::class, 'store'])->name('conflicten.toevoegen');
        Route::delete('/conflicten/{conflict}', [PanelConflictController::class, 'destroy'])->name('conflicten.verwijderen');
        Route::get('/api/schema', [PanelSyncController::class, 'schedule'])->name('api.schema');
        Route::post('/api/scorekaarten', [PanelSyncController::class, 'store'])->name('api.scorekaarten');
    });
});

// Deelnemersportaal
Route::prefix('portaal')->name('portaal.')->group(function (): void {
    Route::middleware('guest:participant')->group(function (): void {
        Route::get('/inloggen', [LoginController::class, 'show'])->name('inloggen');
        Route::post('/inloggen', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('inloggen.verwerken');
        Route::post('/inloggen/link', [LoginController::class, 'sendMagicLink'])->middleware('throttle:5,1')->name('magic.aanvragen');
        Route::get('/wachtwoord-vergeten', [PasswordController::class, 'forgot'])->name('wachtwoord.vergeten');
        Route::post('/wachtwoord-vergeten', [PasswordController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('wachtwoord.vergeten.versturen');
        Route::get('/wachtwoord-herstellen/{token}', [PasswordController::class, 'showReset'])->name('wachtwoord.herstellen');
        Route::post('/wachtwoord-herstellen', [PasswordController::class, 'reset'])->middleware('throttle:5,1')->name('wachtwoord.herstellen.opslaan');
    });

    Route::get('/inloggen/link', MagicLinkController::class)->middleware('signed')->name('magic');

    Route::middleware('auth:participant')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/uitloggen', [LoginController::class, 'logout'])->name('uitloggen');

        Route::get('/profiel/{bedrijf?}', ProfileEditor::class)->name('profiel');

        Route::get('/planning/{bedrijf?}', [PlanningController::class, 'index'])->name('planning');
        Route::post('/planning/{bedrijf}', [PlanningController::class, 'store'])->name('planning.kiezen');
        Route::get('/planning/{bedrijf}/aanleverbewijs', [PlanningController::class, 'proof'])->name('planning.bewijs');
        Route::post('/ophalen/{bedrijf}', [PickupController::class, 'store'])->middleware('throttle:10,1')->name('ophalen');
        Route::post('/ophalen/{bedrijf}/betalen', [PickupController::class, 'pay'])->name('ophalen.betalen');
        Route::get('/ophalen/terug/{order}', [PickupController::class, 'return'])->name('ophalen.terug');

        Route::get('/aanmelding/{bedrijf?}', [RegistrationController::class, 'show'])->name('aanmelding');
        Route::post('/aanmelding/{bedrijf}/korting', [RegistrationController::class, 'discountProof'])->middleware('throttle:10,1')->name('aanmelding.korting');

        Route::get('/rapport/{bedrijf?}', [ReportController::class, 'show'])->name('rapport');
        Route::get('/oorkonde/{bedrijf?}', [ReportController::class, 'certificate'])->name('oorkonde');

        Route::get('/uitslag/{bedrijf?}', [ResultController::class, 'show'])->name('uitslag');
        Route::post('/uitslag/{bedrijf}/bezwaar', [ResultController::class, 'objection'])->middleware('throttle:5,1')->name('bezwaar');
        Route::post('/uitslag/{bedrijf}/finale', [ResultController::class, 'respondToFinal'])->name('finale.reageren');

        Route::get('/marketing/{bedrijf?}', [MarketingController::class, 'index'])->name('marketing');
        Route::get('/marketing/{bedrijf}/beeld/{milestone}/{format}.svg', [MarketingController::class, 'image'])->name('marketing.beeld');
        Route::get('/marketing/{bedrijf}/kit/{milestone}.zip', [MarketingController::class, 'zip'])->name('marketing.kit');
        Route::post('/marketing/{bedrijf}/meting', [MarketingController::class, 'track'])->name('marketing.meting')
            ->withoutMiddleware(PreventRequestForgery::class); // meting via sendBeacon; alleen ingelogd en zonder gevolgen

        Route::get('/cadeaubonnen/{bedrijf?}', [CampaignController::class, 'index'])->name('cadeaubonnen');
        Route::post('/cadeaubonnen/{bedrijf}/winnaar', [CampaignController::class, 'storeWinner'])->middleware('throttle:30,1')->name('cadeaubonnen.winnaar');

        Route::get('/scan/{bedrijf?}', [ScanController::class, 'show'])->name('scan');
        Route::post('/scan/{bedrijf}/verzilveren', [ScanController::class, 'redeem'])->middleware('throttle:60,1')->name('scan.verzilveren');
        Route::post('/scan/{bedrijf}/foto/{redemption}', [ScanController::class, 'photo'])->name('scan.foto');

        Route::get('/sponsoren/{bedrijf?}', [SponsorLinkController::class, 'index'])->name('sponsoren');
        Route::post('/sponsoren/{bedrijf}/{link}', [SponsorLinkController::class, 'respond'])->name('sponsoren.reageren');

        Route::get('/goed-doel/{bedrijf?}', [PortalCharityController::class, 'index'])->name('goed-doel');
        Route::post('/goed-doel/{bedrijf}', [PortalCharityController::class, 'store'])->middleware('throttle:10,1')->name('goed-doel.voordragen');

        Route::get('/facturen', [InvoiceController::class, 'index'])->name('facturen');
        Route::get('/facturen/{invoice}', [InvoiceController::class, 'show'])->name('facturen.toon');

        Route::get('/medewerkers/{bedrijf?}', [StaffController::class, 'index'])->name('medewerkers');
        Route::post('/medewerkers/{bedrijf}', [StaffController::class, 'store'])->name('medewerkers.toevoegen');
        Route::delete('/medewerkers/{bedrijf}/{user}', [StaffController::class, 'destroy'])->name('medewerkers.verwijderen');

        Route::get('/wachtwoord', [PasswordController::class, 'edit'])->name('wachtwoord');
        Route::put('/wachtwoord', [PasswordController::class, 'update'])->name('wachtwoord.opslaan');
    });
});

// Landingspagina's van de contentmachine staan direct onder het domein. Als laatste, zodat elke
// vaste route voorgaat; alleen adressen met een streepje, zoals de machine ze maakt.
Route::get('/{slug}', [ContentController::class, 'landingspagina'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)+')->name('landingspagina');
