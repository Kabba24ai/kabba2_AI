<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderDetailsController extends Controller
{
    public function __invoke($unique_id)
    {
        $order = Order::where('unique_id', $unique_id)->first();

        try {
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ], 404);
            }

            $order->load(['products', 'customer']);

            return response()->json([
                'success' => true,
                'id' => $order->id,
                'unique_id' => $order->unique_id,
                'order_number' => $order->order_number,
                'date' => $order->created_at ? $order->created_at->format(config('app.date.date_format')) : null,
                'total' => $order->grand_total,
                'status' => $order->last_payment_status,
                'customer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'products' => $order->products->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->product_name,
                        'order_products_unique_id' => $p->unique_id,
                        'sku' => $p->product->sku ?? null ,
                        'qty' => $p->quantity,
                        'unit_price' => $p->sub_total,
                        'tax' => $p->tax,
                        'total' => $p->total,
                        'extras' => [
                            $p->service_method,
                            $p->service_option,
                            $p->distance_type,
                        ],
                    ];
                }),
            ], 200);
        } catch (Exception $e) {
            // Log safely: $order may be null
            Log::error('OrderDetailsController error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'order_id' => $order->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load order details.',
                'error' => $e->getMessage(), // optional
            ], 500);
        }
    }
}
