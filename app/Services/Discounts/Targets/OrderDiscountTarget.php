<?php

namespace App\Services\Discounts\Targets;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Customers\CustomerAccount;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\Discounts\Contracts\DiscountTarget;
use App\Services\Discounts\DiscountException;
use App\Services\Discounts\DiscountResult;

/**
 * POD / rental ORDER discount adapter. Pre-posting only: an order that has
 * already posted to the A/R ledger (a customer_accounts type='order' row) or is
 * already fully paid is NOT discountable (Decision 2).
 *
 * Re-pricing keeps `subtotal` = Σ order_products.sub_total untouched (so gross
 * sales reporting is unaffected); the pre-tax discount accrues in
 * `pretax_discount_total`.
 *
 * ORDINARY TAX **and SPECIAL TAX** are both recomputed from the surviving
 * merchandise basis — both are basis-derived, so both must move when the basis
 * moves. **ADDED FEES are flat and protected** and never scale. Whatever else
 * was folded into the original grand total (delivery, coupon) is preserved as
 * an untouched residual.
 *
 * This corrects a live defect. The previous implementation lumped special tax,
 * added fees, delivery and coupon into one undifferentiated residual and
 * preserved the whole thing intact, so a discounted order kept charging
 * special tax on merchandise value the customer was never billed for —
 * verified at $1.00 over-collected on a $200 basis with $4.00 special tax and
 * a $50 discount. It could not do otherwise: without `special_tax_amount` and
 * `added_fees_amount` columns there was no way to reduce one while protecting
 * the other.
 *
 * Original tax, special tax and grand total are snapshotted once
 * (`*_before_discount`), and every recompute derives from that snapshot rather
 * than from current state, so repeated apply/reverse and stacked adjustments
 * stay exact. Arithmetic is integer cents; `decimal(10,2)` remains storage.
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
            $this->order->special_tax_before_discount = $this->order->special_tax_amount;
            $this->order->grand_total_before_discount = $this->order->grand_total;
        }
    }

    /**
     * Re-price the order at a new cumulative pre-tax discount.
     *
     * INTEGER CENTS. Every figure is converted once on read, computed as
     * integers, and converted once on write. `decimal(10,2)` remains the
     * storage type; cents are the arithmetic.
     *
     * WHAT SCALES AND WHAT DOES NOT:
     *
     *   - ordinary tax   — BASIS-DERIVED, scales with the surviving basis
     *   - special tax    — BASIS-DERIVED, scales with the surviving basis
     *   - added fees     — FLAT per unit, PROTECTED, never scales
     *   - other (delivery, coupon, …) — preserved as an untouched residual
     *
     * Special tax scaling is the correction this release exists for. The
     * previous implementation folded special tax into an undifferentiated
     * `otherComponents` residual and preserved it intact, so a discounted
     * order kept charging special tax on merchandise value the customer was
     * never billed for. It could not do otherwise: without
     * `special_tax_amount` and `added_fees_amount` columns there was no way to
     * reduce one while protecting the other. Both now exist.
     *
     * EVERY FIGURE DERIVES FROM THE `*_before_discount` SNAPSHOT, never from
     * current state. That is what makes stacking exact and reversal lossless:
     * N adjustments in any order, and any sequence of apply/reverse, produce
     * the same result as computing once from the original.
     *
     * GROSS IS STABLE. `orders.subtotal` and `order_products.sub_total` are
     * never written here. The concession lives in `pretax_discount_total`.
     */
    private function recompute(float $newPretaxDiscountTotal): void
    {
        $c = static fn ($v): int => (int) round(((float) $v) * 100);

        $subtotalC   = $c($this->order->subtotal);
        $baseTaxC    = $c($this->order->tax_amount_before_discount ?? $this->order->tax_amount);
        $baseSpecialC = $c($this->order->special_tax_before_discount ?? $this->order->special_tax_amount);
        $feesC       = $c($this->order->added_fees_amount);
        $origGrandC  = $c($this->order->grand_total_before_discount ?? $this->order->grand_total);
        $discountC   = max(0, $c($newPretaxDiscountTotal));

        $remainingC = max(0, $subtotalC - $discountC);

        // Both basis-derived components scale by the surviving fraction of the
        // merchandise basis. Equivalent to applying each line's own rate to its
        // reduced basis, because the reduction is proportional across the whole
        // merchandise population.
        $newTaxC     = $subtotalC > 0 ? (int) round($baseTaxC * $remainingC / $subtotalC) : 0;
        $newSpecialC = $subtotalC > 0 ? (int) round($baseSpecialC * $remainingC / $subtotalC) : 0;

        // Whatever else was folded into the original grand total — delivery,
        // coupon — with special tax and added fees now EXCLUDED from the lump
        // because they have columns and are handled explicitly above.
        $otherC = $origGrandC - $subtotalC - $baseTaxC - $baseSpecialC - $feesC;

        $newGrandC = $remainingC + $newTaxC + $newSpecialC + $feesC + $otherC;

        // ── Reconciliation at the service boundary ─────────────────────────
        //
        // Deliberately NOT a restatement of the line above — asserting that
        // a sum equals itself proves nothing. These are the constraints that
        // can actually be violated:

        // 1. ROUND-TRIP. At zero discount the recomputation must reproduce the
        //    original snapshot to the cent. This is the property that makes
        //    reversal lossless and stacking order-independent, and it is the
        //    one that breaks first if a component is dropped from the residual.
        if ($discountC === 0 && $newGrandC !== $origGrandC) {
            throw new DiscountException(
                "Order {$this->order->id}: removing all pre-tax discounts must restore the original "
                ."grand total exactly (expected {$origGrandC}c, computed {$newGrandC}c). Nothing was written."
            );
        }

        // 2. No component may go negative, and neither basis-derived component
        //    may exceed the figure it was derived from.
        if ($newTaxC < 0 || $newSpecialC < 0 || $newGrandC < 0
            || $newTaxC > $baseTaxC || $newSpecialC > $baseSpecialC) {
            throw new DiscountException(
                "Order {$this->order->id}: pre-tax discount recomputation produced an impossible component "
                ."(base {$remainingC}c, tax {$newTaxC}/{$baseTaxC}c, special {$newSpecialC}/{$baseSpecialC}c, "
                ."fees {$feesC}c, other {$otherC}c, total {$newGrandC}c). Nothing was written."
            );
        }

        // 3. The discount may never exceed the gross merchandise basis.
        if ($discountC > $subtotalC) {
            throw new DiscountException(
                "Order {$this->order->id}: pre-tax discount {$discountC}c exceeds gross merchandise "
                ."basis {$subtotalC}c. Nothing was written."
            );
        }

        $this->order->pretax_discount_total = $discountC / 100;
        $this->order->tax_amount            = $newTaxC / 100;
        $this->order->special_tax_amount    = $newSpecialC / 100;
        $this->order->grand_total           = max(0, $newGrandC) / 100;
        $this->order->save();

        $this->propagateToLines($remainingC, $subtotalC, $newTaxC, $newSpecialC);
    }

    /**
     * Keep each line's tax and special tax in step with the order.
     *
     * `orders.special_tax_amount` must always equal the sum of its lines —
     * the invariant established when those columns were added, and the one
     * post-migration verification asserts. Rewriting the order total without
     * the lines would break it on the first discount.
     *
     * Gross `sub_total` and protected `added_fees` are NEVER written. Only the
     * two basis-derived components move, scaled by the same surviving
     * fraction, with the residual cent falling to the last line that actually
     * carried that component so the sums close exactly.
     */
    private function propagateToLines(int $remainingC, int $subtotalC, int $orderTaxC, int $orderSpecialC): void
    {
        if ($subtotalC <= 0) {
            return;
        }

        $lines = OrderProduct::where('order_id', $this->order->id)
            ->orderBy('id')
            ->get(['id', 'sub_total', 'tax', 'special_tax', 'product_data']);

        if ($lines->isEmpty()) {
            return;
        }

        $c = static fn ($v): int => (int) round(((float) $v) * 100);

        foreach (['tax' => $orderTaxC, 'special_tax' => $orderSpecialC] as $column => $orderTotalC) {
            // Proportions come from the ORIGINAL checkout figures, never from
            // the current columns. Scaling already-reduced values would
            // compound across a second adjustment, and a line whose value had
            // rounded to zero could never recover it on reversal.
            $original = [];

            foreach ($lines as $line) {
                $frozen = is_array($line->product_data) ? $line->product_data : null;

                $original[$line->id] = $frozen !== null && array_key_exists($column, $frozen)
                    ? $c($frozen[$column])
                    : $c($line->{$column});   // legacy line with no readable snapshot
            }

            $bearing = $lines->filter(fn ($l) => ($original[$l->id] ?? 0) > 0)->values();

            if ($bearing->isEmpty()) {
                continue;
            }

            $baseTotalC = array_sum(array_map(fn ($l) => $original[$l->id], $bearing->all()));

            if ($baseTotalC <= 0) {
                continue;
            }

            $assigned = 0;
            $last = $bearing->count() - 1;

            foreach ($bearing as $i => $line) {
                $valueC = $i === $last
                    ? $orderTotalC - $assigned
                    : (int) round($original[$line->id] * $orderTotalC / $baseTotalC);

                if ($i !== $last) {
                    $assigned += $valueC;
                }

                OrderProduct::where('id', $line->id)
                    ->update([$column => max(0, $valueC) / 100]);
            }
        }
    }

    private function isPostedToAccount(): bool
    {
        return CustomerAccount::where('order_id', $this->order->id)
            ->where('type', 'order')
            ->exists();
    }
}
