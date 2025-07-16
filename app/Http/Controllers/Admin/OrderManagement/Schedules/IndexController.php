<?php

namespace App\Http\Controllers\Admin\OrderManagement\Schedules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Orders\OrderProduct;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        // Fetch real product-wise order data
        $orderProducts = OrderProduct::with('order','order.customer','order.shippingAddress')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['Pending', 'In Progress']);
            })
            ->whereNotNull('schedule_start_date')
            ->orderBy('schedule_start_date', 'asc')
            ->paginate(10);

        return view('admin.order_management.schedules.index', ['orderProducts' => $orderProducts]);
    }
}
