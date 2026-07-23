<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\OperationsHistory\OrderOperationsHistory;
use Illuminate\Http\Request;

/**
 * Operations History (Enhancement 6/7) — renders the read-only tabbed audit
 * modal body for one order. Loaded on demand (fetch) when the toolbar icon is
 * clicked so Order Details stays light. Read-only: no writes, no side effects.
 */
class OperationsHistoryController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $order = Order::where('unique_id', $unique_id)->firstOrFail();

        $tabs = OrderOperationsHistory::forOrder($order);

        return view('admin.order_management.orders.partials._operations_history_body', [
            'order' => $order,
            'tabs'  => $tabs,
        ]);
    }
}
