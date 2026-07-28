<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\StoreCreditDiscount;

use App\Http\Controllers\Controller;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\DiscountApplicationService;
use Illuminate\Http\Request;

/**
 * Admin: REVERSE an applied Store Credit order discount. Restores the customer's
 * Store Credit balance (a grant — not a cash refund), restores the order's
 * pricing, and appends a compensating discount row (the original is never
 * deleted). Atomic + idempotent.
 */
class RemoveController extends Controller
{
    public function __invoke(Request $request, string $uniqueId, int $discountId)
    {
        $validated = $request->validate([
            'responsible_person' => ['nullable', 'exists:users,id'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();
        $discount = ProductDiscount::where('id', $discountId)
            ->where('target_type', 'order')
            ->where('target_id', $order->id)
            ->firstOrFail();

        app(DiscountApplicationService::class)->reverseStoreCredit(
            $discount,
            isset($validated['responsible_person']) ? (int) $validated['responsible_person'] : null,
            'Store Credit discount removed',
        );

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Store Credit discount removed and balance restored.',
            'final_amount_due' => (float) $order->balance_due,
        ]);
    }
}
