<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

// Models
use App\Models\Iam\Personnel\User;
use App\Models\Customers\Customer;



class CreateController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {

        $customer = Customer::with('orders.products', 'orders.payments', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        // dd($customer);

        return view('admin.crm.customers.create_invoice', ['customer' => $customer]);
    }
}
