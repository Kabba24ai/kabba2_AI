<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\EditRequest;

// Models
use App\Models\Orders\Order;
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
        return view('admin.order_management.orders.edit', compact('order'));
    }
}
