<?php

namespace App\Http\Controllers\Public;

use App\Domain\Platform\Actions\SubscribeToNewsletter;
use App\Domain\Platform\Models\NewsletterSubscription;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request, SubscribeToNewsletter $subscribe): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'bedrijfsnaam' => ['nullable', 'string', 'max:0']], ['bedrijfsnaam.max' => 'Er ging iets mis.'], ['email' => 'e-mailadres']);

        $subscribe($data['email'], 'site', $request->ip());

        return back()->with('nieuwsbrief', 'Bijna klaar: bevestig je inschrijving via de e-mail die we net hebben gestuurd.')->withFragment('nieuwsbrief');
    }

    public function confirm(NewsletterSubscription $subscription): View
    {
        if (! $subscription->isActive()) {
            $subscription->forceFill(['confirmed_at' => now(), 'unsubscribed_at' => null])->save();
        }

        return view('public.paginas.nieuwsbrief', ['title' => 'Inschrijving bevestigd', 'text' => 'Je ontvangt vanaf nu de nieuwsbrief van De Gouden Bol. Afmelden kan via de link onderaan iedere mail.']);
    }

    public function unsubscribe(NewsletterSubscription $subscription): View
    {
        $subscription->forceFill(['unsubscribed_at' => now()])->save();

        return view('public.paginas.nieuwsbrief', ['title' => 'Afgemeld', 'text' => 'Je bent afgemeld voor de nieuwsbrief. Je ontvangt geen mails meer van De Gouden Bol.']);
    }
}
