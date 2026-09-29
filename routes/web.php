<?php

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
use App\Http\Controllers\Portal\PlanningController;
use App\Http\Controllers\Portal\ResultController;
use App\Http\Controllers\Portal\ScanController;
use App\Http\Controllers\Portal\SponsorLinkController;
use App\Http\Controllers\Portal\StaffController;
use App\Http\Controllers\Public\BakkerController;
use App\Http\Controllers\Public\CharityController;
use App\Http\Controllers\Public\EditionArchiveController;
use App\Http\Controllers\Public\FakeCheckoutController;
use App\Http\Controllers\Public\FinaleController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PressController;
use App\Http\Controllers\Public\ProvinceController;
use App\Http\Controllers\Public\RecognitionController;
use App\Http\Controllers\Public\RegistrationStatusController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\SponsorController;
use App\Http\Controllers\Public\VoucherController;
use App\Http\Controllers\Staff\LabelController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Webhooks\MollieWebhookController;
use App\Livewire\Portal\ProfileEditor;
use App\Livewire\Public\RegistrationWizard;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

// Publiekssite
Route::get('/', HomeController::class)->name('home');
Route::get('/provincies', [ProvinceController::class, 'index'])->name('provincies.index');
Route::get('/provincie/{province}', [ProvinceController::class, 'show'])->name('provincies.show');
Route::get('/bakkers', [BakkerController::class, 'index'])->name('bakkers.index');
Route::get('/bakker/{company}', [BakkerController::class, 'show'])->name('bakkers.toon');
Route::get('/finale', FinaleController::class)->name('finale');
Route::get('/erkenning', [RecognitionController::class, 'lookup'])->name('erkenning.zoek');
Route::get('/erkenning/{recognition}', [RecognitionController::class, 'show'])->name('erkenning.toon');
Route::get('/erkenning/{recognition}/badge.svg', [RecognitionController::class, 'badge'])->name('erkenning.badge');
Route::get('/edities', [EditionArchiveController::class, 'index'])->name('edities.index');
Route::get('/editie/{edition}', [EditionArchiveController::class, 'show'])->name('edities.show');
Route::get('/hoe-werkt-de-test', [PageController::class, 'hoeWerktDeTest'])->name('hoe-werkt-de-test');
Route::get('/het-panel', [PageController::class, 'panel'])->name('panel');
Route::get('/nieuws', [NewsController::class, 'index'])->name('nieuws.index');
Route::get('/nieuws/{post}', [NewsController::class, 'show'])->name('nieuws.toon');
Route::get('/voorwaarden', [PageController::class, 'voorwaarden'])->name('voorwaarden');
Route::get('/actievoorwaarden', [PageController::class, 'actievoorwaarden'])->name('actievoorwaarden');
Route::get('/winnaars', [VoucherController::class, 'winners'])->name('winnaars');
Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/cadeaubon/claim/{token}', [VoucherController::class, 'claim'])->name('cadeaubon.claim');
    Route::post('/cadeaubon/claim/{token}', [VoucherController::class, 'store'])->name('cadeaubon.claim.bevestigen');
    Route::get('/bon/{token}', [VoucherController::class, 'show'])->name('bon.toon');
});
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/pers', [PressController::class, 'index'])->name('pers');
Route::get('/pers/kit/{pressRelease}', [PressController::class, 'kit'])->middleware('signed')->name('pers.kit');
Route::get('/pers/{pressRelease}', [PressController::class, 'show'])->name('pers.toon');
Route::get('/sponsoren', SponsorController::class)->name('sponsoren');
Route::get('/goede-doelen', CharityController::class)->name('goede-doelen');
Route::post('/nieuwsbrief', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('nieuwsbrief.aanmelden')
    ->withoutMiddleware(PreventRequestForgery::class); // formulier staat op gecachte pagina's; honeypot + throttle + dubbele opt-in
Route::get('/nieuwsbrief/bevestigen/{subscription}', [NewsletterController::class, 'confirm'])->middleware('signed')->name('nieuwsbrief.bevestigen');
Route::get('/nieuwsbrief/afmelden/{subscription}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('nieuwsbrief.afmelden');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/stijlgids', StyleguideController::class)->name('stijlgids');

// Aanmelden
Route::get('/aanmelden', RegistrationWizard::class)->name('aanmelden');
Route::get('/aanmelden/status/{order}', [RegistrationStatusController::class, 'show'])->name('aanmelden.status');
Route::post('/aanmelden/status/{order}/opnieuw', [RegistrationStatusController::class, 'retry'])->name('aanmelden.opnieuw');

// Betalingen
Route::get('/betaling/test/{payment}', [FakeCheckoutController::class, 'show'])->name('betaling.fake');
Route::post('/betaling/test/{payment}', [FakeCheckoutController::class, 'settle'])->name('betaling.fake.afronden');
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
