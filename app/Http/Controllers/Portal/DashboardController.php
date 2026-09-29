<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Models\ParticipantUser;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var ParticipantUser $user */
        $user = $request->user('participant');
        $edition = Edition::current();

        $companies = $user->companies()
            ->with(['entries' => fn ($query) => $query->where('edition_id', $edition?->getKey())->with(['province', 'order.invoice', 'order.latestPayment', 'deliverySlot', 'publicationItems.batch'])])
            ->get();

        return view('portal.dashboard', [
            'user' => $user,
            'edition' => $edition,
            'companies' => $companies,
        ]);
    }
}
