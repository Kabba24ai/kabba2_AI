<?php

namespace App\Http\Controllers\Admin\ServiceManagement\CustomerDamage;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Service\CustomerDamageStaging;
use Illuminate\Http\Request;

/**
 * Manual Customer Damage creation — web-only, for an authorized user who
 * discovers obvious customer damage during return handling. Order-based by
 * rule: no order, no Customer Damage record (Rental Ready / Service intake
 * own orderless damage). Customer, equipment, store, reporter, and
 * timestamp are all DERIVED server-side — the form supplies only the
 * order, the order product, and the observation.
 */
class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'order_id'         => ['required', 'integer', 'exists:orders,id'],
            'order_product_id' => ['required', 'integer', 'exists:order_products,id'],
            'observation'      => ['required', 'string', 'max:2000'],
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $orderProduct = OrderProduct::findOrFail($validated['order_product_id']);

        // Integrity: the product/equipment must belong to the selected
        // order — cross-order linkage is rejected server-side.
        if ((int) $orderProduct->order_id !== (int) $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'The selected item does not belong to the selected order.',
            ], 422);
        }

        $staging = CustomerDamageStaging::create([
            'source_type'      => 'manual',
            'order_id'         => $order->id,
            'customer_id'      => $order->customer_id,
            'order_product_id' => $orderProduct->id,
            'equipment_id'     => $orderProduct->equipment_id,
            'store_id'         => $orderProduct->pickup_store_id ?? $orderProduct->delivery_store_id,
            'observation'      => trim($validated['observation']),
            'reported_by'      => auth()->id(),
            'reported_at'      => now(),
            'status'           => CustomerDamageStaging::STATUS_NEW,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Customer damage {$staging->unique_id} recorded.",
        ]);
    }
}
