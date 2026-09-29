<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Commerce\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $companyIds = $request->user('participant')->companies()->pluck('companies.id');

        $invoices = Invoice::query()
            ->whereHas('order', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->with('order.orderable')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        return view('portal.facturen.index', ['invoices' => $invoices]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $companyIds = $request->user('participant')->companies()->pluck('companies.id');

        abort_unless($companyIds->contains($invoice->order->company_id), 403);

        return view('portal.facturen.show', [
            'invoice' => $invoice->load('order.lines'),
            'issuer' => config('commerce.issuer'),
        ]);
    }
}
