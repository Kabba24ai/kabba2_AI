<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\WaitList\EquipmentWaitList;

class ShowController extends Controller
{
    public function __invoke(EquipmentWaitList $waitList)
    {
        $waitList->load([
            'customer', 'category', 'store', 'items.equipment', 'selectedProducts', 'convertedOrder',
            'createdBy', 'cancelledBy',
            'communications.user', 'alerts.equipment', 'alerts.acknowledgedBy',
        ]);

        // Recent orders for the manual Convert action (staff links the
        // reservation/order they created — nothing is created automatically)
        $orders = Order::latest('id')->limit(300)->get(['id', 'order_number', 'customer_name']);

        return view('admin.wait_list.show', compact('waitList', 'orders'));
    }
}
