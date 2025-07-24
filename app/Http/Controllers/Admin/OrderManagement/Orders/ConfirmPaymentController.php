<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;


// Models
use App\Models\Orders\Order;

class ConfirmPaymentController extends Controller
{
    /**
     * Handle updating of orders.
     */
    public function __invoke($uniqueId)
    {


        $order = Order::where('unique_id', $uniqueId)->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        try {
            $lastPayment = $order->payments()->pending()->cod()->latest()->first();
            if (!$lastPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payments found for this order.'
                ], 404);
            }

            $lastPayment->status = 'Paid';
            $lastPayment->save();

            return response()->json([
                'success' => true,
                'message' => 'Order payment confirmed!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not confirm order payment.'
            ], 500);
        }
    }
}
