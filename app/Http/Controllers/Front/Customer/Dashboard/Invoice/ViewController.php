<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CustomHelper;

use App\Helpers\ConfigurationHelper;

class ViewController extends Controller
{
    /**
     * View one of the AUTHENTICATED customer's own invoices.
     *
     * Security: the invoice is resolved through the session customer's
     * invoices() relationship (constrained to customer_id = auth id), so
     * another customer's invoice 404s instead of disclosing its line items,
     * totals, and internal approver names.
     */
    public function __invoke(Request $request, $unique_id)
    {
        $customer = Auth::guard('customer')->user();

        $invoice = $customer->invoices()->with([
            'creator',
            'customer',
            'items.orderProduct',
        ])->where('unique_id', $unique_id)->firstOrFail();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        $customer->load('orders.products', 'orders.payments', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media');

        return view('front.customer.dashboard.view_invoice', ['invoice' => $invoice, 'sales_tax' => $sales_tax, 'customer' => $customer]);
    }
}
