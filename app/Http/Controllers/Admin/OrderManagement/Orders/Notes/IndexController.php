<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Notes;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke($orderUniqueId)
    {
        $order = Order::with(['notes'])->where('unique_id', $orderUniqueId)->firstOrFail();
        $notes = $order->notes;
        $html = view('components.admin.order-management.orders.order-notes-list', ['notes' => $notes])->render();
        return response()->json([
            'success' => true,
            'message' => 'Notes retrieved successfully.',
            'html' => $html,
        ]);
    }
}
