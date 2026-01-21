<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Models
use App\Models\Iam\Personnel\User;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Helpers\ConfigurationHelper;

class CreateController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {

        $customer = Customer::with('orders.products', 'orders.payments', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        $order = Order::with('shippingAddress', 'products.product.categories', 'lastPayment', 'products.deliverySignatureMedia', 'products.returnSignatureMedia')->where('customer_id', $customer->id)->whereNull('invoice_id')->get();

        $users = User::where('status', 'Active')->orderBy('first_name')->get();

        $sales_tax = ConfigurationHelper::getSettings(null , 'sales_tax');


        $invoiceItems = []; // your array
        $jsonInvoiceItems = json_encode($invoiceItems); // now it's a JSON string

// dd($order);
        return view('admin.crm.customers.create_invoice', ['customer' => $customer , 'invoiceItems' => $jsonInvoiceItems , 'orders'=> $order, 'users' => $users,'sales_tax' => $sales_tax,]);

    }
}
