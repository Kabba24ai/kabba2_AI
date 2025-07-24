<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

// Request
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\PaymentStoreRequest;
use Illuminate\Support\Carbon;
use App\Helpers\CustomHelper;
use App\Models\Iam\Personnel\User ;

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
