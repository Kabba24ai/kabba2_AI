<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

use App\Models\Customers\Invoice;
use App\Helpers\ConfigurationHelper;

class ViewController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {

        $invoice = Invoice::with([
            'creator',
            'customer',
            'items.orderProduct',
        ])->where('unique_id', $unique_id)->first();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');


        $customer = Customer::with('orders.products', 'orders.payments', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media')
            ->where('unique_id', $invoice->customer->unique_id)
            ->firstOrFail();

        return view('front.customer.dashboard.view_invoice', ['invoice' => $invoice, 'sales_tax' => $sales_tax, 'customer' => $customer]);
    }
}
