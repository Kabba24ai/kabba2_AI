<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Reorder;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;

class IndexController extends Controller
{
    public function __invoke($orderUniqueId, $type)
    {

        $order = Order::where('unique_id', $orderUniqueId)->firstOrFail();

        $customerCards = $order->customer->cards()->pluck('card_number', 'id')->prepend('Select Card', '');
        return view('admin.order_management.orders.reorder.index', [
            'order' => $order,
            'type' => $type,
            'customerCards' => $customerCards
        ]);
    }
}
