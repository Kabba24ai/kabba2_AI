<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\StoreCreditDiscount;

use App\Enums\Discounts\DiscountTargetType;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\CustomerCreditService;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\DiscountException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Admin: APPLY a Store Credit DISCOUNT (pre-tax) to a POD order — NOT a payment.
 * Server-authoritative: the discount engine validates ownership, balance, and
 * eligibility, redeems Store Credit (never a payment row), and re-prices the
 * order (reduced tax + grand_total). The remainder is collected through the
 * normal payment system.
 */
class ApplyController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $validated = $request->validate([
            'store_credit_discount' => ['required', 'numeric', 'min:0.01'],
            'responsible_person'    => ['required', 'exists:users,id'],
            'request_uuid'          => ['nullable', 'string', 'max:64'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();
        $key = 'order_scd:' . ($validated['request_uuid'] ?? (string) Str::uuid());

        try {
            $discount = app(DiscountApplicationService::class)->applyStoreCredit(
                DiscountTargetType::Order,
                (int) $order->id,
                (float) $validated['store_credit_discount'],
                $key,
                (int) $validated['responsible_person'],
                'Store Credit discount',
                'admin_order_payment',
                $order->customer_id ? (int) $order->customer_id : null,
            );
        } catch (DiscountException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Store Credit discount applied.',
            'discount_id' => $discount->id,
            'summary' => [
                'original_product_value' => (float) $discount->original_product_value,
                'store_credit_applied'   => (float) $discount->calculated_discount_amount,
                'available_store_credit'  => CustomerCreditService::remainingBalance((int) $order->customer_id),
                'adjusted_product_value' => (float) $discount->discounted_product_value,
                'sales_tax'              => (float) $order->tax_amount,
                'final_amount_due'       => (float) $order->balance_due,
            ],
        ]);
    }
}
