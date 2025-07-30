<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;


use App\Http\Controllers\Controller;

use App\Models\Customers\CustomerAccount;


use Barryvdh\DomPDF\Facade\Pdf;


class DownloadPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($id)
    {           
            $transaction = CustomerAccount::with(['customer', 'order', 'responsibleUser'])->findOrFail($id);

            $pdf = Pdf::loadView('admin.crm.customers.transaction_accounts_pdf', compact('transaction'));

            return $pdf->download('Transaction_' . $transaction->unique_id . '.pdf');
        
    }

}
