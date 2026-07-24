<?php

namespace App\Http\Controllers\Admin\ServiceManagement\CustomerDamage;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

/**
 * Read-only: the products/equipment of one order, for the manual Customer
 * Damage form's second step (order first, then the damaged item — the
 * server re-validates the relationship on submit regardless).
 */
class OrderProductLookupController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::with('products.equipment')->findOrFail($validated['order_id']);

        return response()->json([
            'success' => true,
            'results' => $order->products->map(fn ($p) => [
                'id'    => $p->id,
                'label' => trim(($p->product_name ?? 'Item')
                    . ($p->equipment?->equipment_name ? ' — ' . $p->equipment->equipment_name : '')
                    . ($p->equipment?->equipment_id ? ' (' . $p->equipment->equipment_id . ')' : '')),
            ])->values(),
        ]);
    }
}
