<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderDetailsController extends Controller
{
    public function __invoke($unique_id)
    {
        $order = Order::where('unique_id', $unique_id)->first();

        //  Get related customer account entry
        $account = CustomerAccount::where('order_id', $order->id)
            ->first();   

        $account_id = null ;

        $account_id = $account?->unique_id; // null safe

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
                'accountid'=>$account_id ?? null ,
                'unique_id' => $order->unique_id,
                'order_number' => $order->order_number,
                'date' => $order->created_at ? $order->created_at->format(config('app.date.date_format')) : null,
                'total' => $order->grand_total,
                // Payment Architecture Finalization (Tier 2): the order's
                // aggregate collection/refund state, not just its most
                // recent payment row's status.
                'status' => \App\Services\PaymentDescriptionPresenter::orderStatusLabel(
                    \App\Services\Orders\OrderPaymentSummary::for($order)
                ),
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
