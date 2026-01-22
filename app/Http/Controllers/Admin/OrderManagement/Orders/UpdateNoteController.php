<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\UpdateNoteRequest;

// Models
use App\Models\Orders\Order;

class UpdateNoteController extends Controller
{
    /**
     * Handle updating of orders.
     */
    public function __invoke(UpdateNoteRequest $request, $uniqueId)
    {
        $validatedData = $request->validated();

        $order = Order::where('unique_id', $uniqueId)->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        try {
            $order->order_note = $validatedData['order_note'];
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order note updated!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not update order note.'
            ], 500);
        }
    }
}
