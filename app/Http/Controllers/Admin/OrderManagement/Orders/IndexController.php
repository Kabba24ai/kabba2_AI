<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Orders\Order;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
         // Fetch orders from the database, most recent first
        $orders = Order::orderByDesc('id')->paginate(10);

        return view('admin.order_management.orders.index', [
            'orders' => $orders
        ]);
    }
}
