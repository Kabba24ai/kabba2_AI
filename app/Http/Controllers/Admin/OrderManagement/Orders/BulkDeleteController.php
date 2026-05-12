<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\BulkDeleteRequest;
use App\Models\Orders\Order;

class BulkDeleteController extends Controller
{
    /**
     * Handle bulk deletion of orders.
     *
     * Each order is soft-deleted individually so that the Order model's
     * deleting boot hook fires and moves assigned equipment to Maintenance hold.
     * A mass delete() call would bypass model events.
     */
    public function __invoke(BulkDeleteRequest $request)
    {
        $uniqueIds = $request->validated()['unique_ids'] ?? [];

        if (empty($uniqueIds)) {
            return response()->json(['message' => 'No orders selected for deletion.'], 422);
        }

        DB::transaction(function () use ($uniqueIds) {
            Order::whereIn('unique_id', $uniqueIds)
                ->get()
                ->each(fn($order) => $order->delete());
        });

        return response()->json([
            'success' => true,
            'message' => 'Order(s) deleted successfully.',
        ], 200);
    }
}
