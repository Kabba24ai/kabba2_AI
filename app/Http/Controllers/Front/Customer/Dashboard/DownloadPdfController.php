<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;


use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;

use Barryvdh\DomPDF\Facade\Pdf;


class DownloadPdfController extends Controller
{
    /**
     * Download one of the AUTHENTICATED customer's own ledger transactions.
     *
     * Security: resolve the transaction through the session customer's
     * accounts() relationship, so findOrFail is constrained to customer_id =
     * auth id. A transaction belonging to another customer 404s instead of
     * exposing their financial record.
     */
    public function __invoke($id)
    {
            $transaction = Auth::guard('customer')->user()
                ->accounts()
                ->with(['customer', 'order', 'responsibleUser'])
                ->findOrFail($id);

            $pdf = Pdf::loadView('admin.crm.customers.transaction_accounts_pdf', compact('transaction'));

            return $pdf->download('Transaction_' . $transaction->unique_id . '.pdf');
        
    }

}
