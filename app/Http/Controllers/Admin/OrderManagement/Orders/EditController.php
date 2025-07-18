<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\EditRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
// Models
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use Request;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($uniqueid)
    {
        $order = Order::with(['products.product', 'billingAddress', 'shippingAddress'])->where('unique_id', $uniqueid)->firstOrFail();

        $stores = Store::orderBy('store_name')->get();
        $employees = User::orderBy('first_name')->get();
        $states = State::orderBy('name')->get();
        return view('admin.order_management.orders.edit', compact('order', 'stores', 'employees', 'states'));
    }
}
