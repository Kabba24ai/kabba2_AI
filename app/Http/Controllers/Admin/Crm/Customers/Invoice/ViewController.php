<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

use App\Models\Customers\Invoice;

class ViewController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {

        $invoice = Invoice::with('creator', 'items', 'customer')->where('unique_id', $unique_id)->first();

        // dd($invoice);

        return view('admin.crm.customers.view_invoice', ['invoice' => $invoice]);
    }
}
