<?php

namespace App\Services\Discounts\Targets;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Customers\CustomerAccount;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\Contracts\DiscountTarget;
use App\Services\Discounts\DiscountResult;

/**
 * POD / rental ORDER discount adapter. Pre-posting only: an order that has
 * already posted to the A/R ledger (a customer_accounts type='order' row) or is
 * already fully paid is NOT discountable (Decision 2).
 *
 * Re-pricing keeps `subtotal` = Σ order_products.sub_total untouched (so gross
 * sales reporting is unaffected); the pre-tax discount accrues in
 * `pretax_discount_total`. Tax is recomputed with a BLENDED effective rate
 * (original tax ÷ subtotal) so mixed taxable/exempt lines are preserved, and
 * grand_total is recomputed while preserving the non-product components
 * (special tax, added fees, delivery, coupon) that are folded into grand_total.
 * Original tax + grand_total are snapshotted once so repeated apply/reverse stay
 * exact.
 */
class OrderDiscountTarget implements DiscountTarget
{
    private Order $order;

    public function __construct(int $orderId)
    {
        $this->order = Order::findOrFail($orderId);
    }

    public function targetType(): DiscountTargetType
    {
        return DiscountTargetType::Order;
    }

    public function targetId(): int
    {
        return (int) $this->order->id;
    }

    public function customerId(): ?int
    {
        return $this->order->customer_id ? (int) $this->order->customer_id : null;
    }

    public function lockAndRefresh(): void
    {
        $this->order = Order::where('id', $this->order->id)->lockForUpdate()->firstOrFail();
    }

    public function isDiscountable(): bool
    {
        return $this->ineligibleReason() === null;
    }

    public function ineligibleReason(): ?string
    {
        if ($this->isPostedToAccount()) {
            return 'Order is already posted to the Credit Account (post-posting discounts are out of scope).';
        }
        if (round((float) $this->order->balance_due, 2) <= 0) {
            return 'Order has no remaining balance to discount.';
        }
        return null;
    }

    public function eligibleProductValue(): float
    {
        $subtotal = (float) $this->order->subtotal;
        $alreadyDiscounted = (float) $this->order->pretax_discount_total;

        return round(max(0.0, $subtotal - $alreadyDiscounted), 2);
    }

    public function taxRate(): float
    {
        $subtotal = (float) $this->order->subtotal;
        if ($subtotal <= 0) {
            return 0.0;
        }
        // Blended effective rate off the ORIGINAL (undiscounted) tax.
        $baseTax = (float) ($this->order->tax_amount_before_discount ?? $this->order->tax_amount);

        return $baseTax / $subtotal;
    }

    public function applyDiscount(DiscountResult $result, ProductDiscount $discount): void
    {
        $this->captureOriginalSnapshotOnce();
        $newPretax = round((float) $this->order->pretax_discount_total + $result->discountAmount, 2);
        $this->recompute($newPretax);
    }

    public function reverseDiscount(ProductDiscount $original): void
    {
        $newPretax = round((float) $this->order->pretax_discount_total - (float) $original->calculated_discount_amount, 2);
        $this->recompute(max(0.0, $newPretax));
    }

    // ── internals ──────────────────────────────────────────────────────────

    private function captureOriginalSnapshotOnce(): void
    {
        if ($this->order->grand_total_before_discount === null) {
            $this->order->tax_amount_before_discount = $this->order->tax_amount;
            $this->order->grand_total_before_discount = $this->order->grand_total;
        }
    }

    private function recompute(float $newPretaxDiscountTotal): void
    {
        $subtotal = (float) $this->order->subtotal;
        $baseTax = (float) ($this->order->tax_amount_before_discount ?? $this->order->tax_amount);
        $origGrand = (float) ($this->order->grand_total_before_discount ?? $this->order->grand_total);

        $rate = $subtotal > 0 ? $baseTax / $subtotal : 0.0;
        $remainingBase = round($subtotal - $newPretaxDiscountTotal, 2);
        $newTax = round($remainingBase * $rate, 2);

        // Everything in grand_total that is NOT product base or product tax
        // (special tax + added fees + delivery − coupon), preserved intact.
        $otherComponents = round($origGrand - $subtotal - $baseTax, 2);
        $newGrand = round($remainingBase + $newTax + $otherComponents, 2);

        $this->order->pretax_discount_total = $newPretaxDiscountTotal;
        $this->order->tax_amount = $newTax;
        $this->order->grand_total = max(0.0, $newGrand);
        $this->order->save();
    }

    private function isPostedToAccount(): bool
    {
        return CustomerAccount::where('order_id', $this->order->id)
            ->where('type', 'order')
            ->exists();
    }
}
