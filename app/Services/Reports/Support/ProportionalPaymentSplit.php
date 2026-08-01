<?php

namespace App\Services\Reports\Support;

/**
 * The sole allocation primitive for cash-basis financial reporting: distribute
 * an order-level figure (tax, discount, a component sub-total, …) across the
 * order's collected payments, proportional to the order's CANONICAL total
 * (grand_total) — never to the set of payments seen so far.
 *
 * Every collected-revenue surface (Sales Summary, Sales Tax Stream A, Payment
 * Reconciliation Ledger Stream A, dashboard KPIs) allocates through this class
 * via CollectedRevenueQuery. The surfaces reconcile because none of them
 * reproduce these formulas independently.
 *
 * TEMPORAL-STABILITY INVARIANT (load-bearing — do not regress):
 *   Once a payment exists, its calculated allocation must NOT change when a
 *   later payment is recorded. A June partial payment reported in a filed
 *   period must read the same after a July payment is added.
 *
 * This holds because:
 *   - the proportional denominator is the fixed canonical order total, so a
 *     payment's proportional share does not depend on which other payments
 *     exist; and
 *   - each allocation depends only on payments at or before it chronologically
 *     (via the running totals), so appending a later payment cannot alter an
 *     earlier one.
 *
 * The complete payment set is used ONLY to: preserve chronological order,
 * identify the payment that completes the balance, assign the final-cent
 * remainder, and cap cumulative allocation at the order's stored figure (so an
 * overpayment never allocates more tax than the order actually carries).
 *
 * Convention: whole-cent arithmetic; amounts must be passed in chronological
 * (collection) order.
 */
class ProportionalPaymentSplit
{
    /**
     * Allocate one order-level figure across the payments.
     *
     * @param  float   $target      the order's stored figure to distribute (tax, discount, …)
     * @param  float   $orderTotal  the order's canonical total (grand_total) — the fixed denominator
     * @param  float[] $amounts     collected payment amounts, in chronological order
     * @return float[] aligned to $amounts; lifetime sum == $target once the order is fully collected
     */
    public static function allocate(float $target, float $orderTotal, array $amounts): array
    {
        $amounts         = array_values($amounts);
        $targetCents     = (int) round($target * 100);
        $orderTotalCents = (int) round($orderTotal * 100);

        $runningAmountCents = 0;
        $runningTargetCents = 0;
        $out                = [];

        foreach ($amounts as $amount) {
            $amountCents         = (int) round($amount * 100);
            $runningAmountCents += $amountCents;

            $remainingCents = max(0, $targetCents - $runningTargetCents);

            // "Completes the balance": cumulative collected has reached the
            // order's canonical total. This payment absorbs the rounding
            // remainder so the lifetime allocation lands exactly on the order's
            // stored figure.
            $completes = $orderTotalCents > 0 && $runningAmountCents >= $orderTotalCents;

            if ($completes) {
                $cents = $remainingCents;
            } else {
                // Proportional to the CANONICAL order total (fixed) — this is
                // what makes an already-computed allocation stable when later
                // payments arrive. Capped at what is left so cumulative
                // allocation can never exceed the order's stored figure.
                $prop  = $orderTotalCents > 0 ? (int) round($targetCents * $amountCents / $orderTotalCents) : 0;
                $cents = min($prop, $remainingCents);
            }

            $runningTargetCents += $cents;
            $out[]               = round($cents / 100, 2);
        }

        return $out;
    }

    /**
     * Portion of each payment applied toward the order's canonical total, and
     * the excess beyond it (the overpayment). Overpayments are surfaced
     * separately by the reports and never create revenue or tax.
     *
     * @param  float   $orderTotal  the order's canonical total (grand_total)
     * @param  float[] $amounts     collected payment amounts, in chronological order
     * @return array<int, array{applied: float, overpayment: float}> aligned to $amounts
     */
    public static function applied(float $orderTotal, array $amounts): array
    {
        $amounts            = array_values($amounts);
        $orderTotalCents    = (int) round($orderTotal * 100);
        $runningAmountCents = 0;
        $out                = [];

        foreach ($amounts as $amount) {
            $amountCents  = (int) round($amount * 100);
            $capacityLeft = max(0, $orderTotalCents - $runningAmountCents);
            $appliedCents = $orderTotalCents > 0 ? min($amountCents, $capacityLeft) : $amountCents;

            $runningAmountCents += $amountCents;

            $out[] = [
                'applied'     => round($appliedCents / 100, 2),
                'overpayment' => round(($amountCents - $appliedCents) / 100, 2),
            ];
        }

        return $out;
    }

    /**
     * Partition one already-allocated payment figure across the order's LINES,
     * proportional to the given weights (line sub-totals for base/discount,
     * line tax values for tax — so a non-taxable line never receives tax).
     *
     * This is the line-attribution counterpart of allocate(): allocate() slices
     * an order figure across PAYMENTS (canonical-total denominator, completer
     * remainder); distribute() slices one payment's slice across LINES (weight
     * denominator, deterministic remainder). Filtered reports never recalculate
     * tax or discount — they sum these partitions, which by construction sum
     * back to the payment's canonical figure exactly.
     *
     * Deterministic final-cent remainder: the LAST line with a nonzero weight
     * (in the caller's stable line order) absorbs it. Degenerate-data fallback:
     * if every weight is zero but the target isn't, the last line takes the
     * full target — additivity (Σ parts == target) must never break.
     *
     * @param  float   $target  the payment-level figure to partition
     * @param  float[] $weights one weight per line, in stable (line-id) order
     * @return float[] aligned to $weights; Σ == $target exactly
     */
    public static function distribute(float $target, array $weights): array
    {
        $weights     = array_values($weights);
        $count       = count($weights);
        $targetCents = (int) round($target * 100);

        if ($count === 0) {
            return [];
        }

        $totalWeight = array_sum($weights);

        // Last nonzero-weight index takes the remainder; degenerate all-zero
        // weights fall back to the last line so Σ parts always equals target.
        $remainderIndex = $count - 1;
        if ($totalWeight > 0) {
            for ($i = $count - 1; $i >= 0; $i--) {
                if ($weights[$i] > 0) {
                    $remainderIndex = $i;
                    break;
                }
            }
        }

        $out           = array_fill(0, $count, 0.0);
        $runningCents  = 0;

        foreach ($weights as $i => $weight) {
            if ($i === $remainderIndex) {
                continue; // assigned last, below
            }
            $cents = $totalWeight > 0
                ? (int) round($targetCents * $weight / $totalWeight)
                : 0;
            $runningCents += $cents;
            $out[$i]       = round($cents / 100, 2);
        }

        $out[$remainderIndex] = round(($targetCents - $runningCents) / 100, 2);

        return $out;
    }

    /**
     * Convenience: allocate tax and discount together.
     *
     * @param  float[] $amounts collected payment amounts, in chronological order
     * @return array<int, array{tax: float, discount: float}> aligned to $amounts
     */
    public static function split(float $orderTax, float $orderDiscount, float $orderTotal, array $amounts): array
    {
        $tax      = self::allocate($orderTax, $orderTotal, $amounts);
        $discount = self::allocate($orderDiscount, $orderTotal, $amounts);

        $out = [];
        foreach ($tax as $i => $t) {
            $out[] = ['tax' => $t, 'discount' => $discount[$i]];
        }

        return $out;
    }
}
