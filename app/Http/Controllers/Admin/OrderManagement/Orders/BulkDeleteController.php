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
     */
    public function __invoke(BulkDeleteRequest $request)
    {
        $uniqueIds = $request->validated()['unique_ids'] ?? [];

        if (empty($uniqueIds)) {
            return response()->json(['message' => 'No orders selected for deletion.'], 422);
        }

        DB::transaction(function () use ($uniqueIds) {
            Order::whereIn('unique_id', $uniqueIds)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Order(s) deleted successfully.',
        ], 200);
    }
}
