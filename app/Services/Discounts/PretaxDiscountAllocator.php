<?php

namespace App\Services\Discounts;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Facades\DB;

/**
 * Distributes a pre-tax adjustment across an order's lines, and records which
 * adjustment produced which share.
 *
 * WHY DETERMINISM MATTERS. The same order and the same adjustment must always
 * produce the same distribution. A reversal removes exactly the rows an apply
 * created, so if the distribution could vary — by row order, by float drift,
 * by tie-breaking — a reverse would not undo an apply and the order would
 * drift a cent at a time.
 *
 * THE RULE:
 *   - proportional to each line's GROSS `sub_total`, across every merchandise
 *     line, because that is the population the adjustment actually reduces;
 *   - integer cents;
 *   - leftover cents by LARGEST FRACTIONAL REMAINDER;
 *   - ties broken by ASCENDING order_product_id, never by array order.
 *
 * GROSS IS NEVER WRITTEN. `sub_total` is read as the allocation weight and is
 * never modified. Only `pretax_discount_allocated` — a denormalized sum of
 * this line's active allocations — is maintained alongside the rows.
 */
class PretaxDiscountAllocator
{
    /**
     * Allocate one adjustment's amount across an order's lines.
     *
     * Idempotent: a retry for the same `product_discount_id` rewrites the same
     * rows to the same values rather than creating a second set, backed by the
     * unique index on (product_discount_id, order_product_id).
     *
     * @param  int  $amountCents  the adjustment's own amount, not the cumulative total
     * @return array<int,int> order_product_id => allocated cents
     */
    public static function allocate(Order $order, int $productDiscountId, int $amountCents): array
    {
        $lines = self::lines($order);

        if ($lines->isEmpty() || $amountCents <= 0) {
            return [];
        }

        $shares = self::distribute($lines, $amountCents);

        DB::transaction(function () use ($order, $productDiscountId, $shares) {
            foreach ($shares as $lineId => $cents) {
                $existing = DB::table('order_product_discount_allocations')
                    ->where('product_discount_id', $productDiscountId)
                    ->where('order_product_id', $lineId)
                    ->first();

                if ($existing === null) {
                    DB::table('order_product_discount_allocations')->insert([
                        'product_discount_id' => $productDiscountId,
                        'order_product_id'    => $lineId,
                        'order_id'            => $order->id,
                        'allocated_amount'    => $cents / 100,
                        'reversed_at'         => null,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);

                    continue;
                }

                // A retry rewrites the amount, but NEVER clears reversed_at.
                // Resurrecting a deliberately reversed allocation because the
                // same call arrived twice would silently re-apply a concession
                // the business had withdrawn.
                DB::table('order_product_discount_allocations')
                    ->where('id', $existing->id)
                    ->update(['allocated_amount' => $cents / 100, 'updated_at' => now()]);
            }

            self::refreshLineTotals($order);
        });

        return $shares;
    }

    /**
     * Deactivate one adjustment's allocations, leaving every other
     * adjustment's rows exactly as they are.
     *
     * Rows are stamped, never deleted — the record of what was allocated and
     * when it stopped applying is part of the audit trail.
     */
    public static function reverse(Order $order, int $productDiscountId): void
    {
        DB::transaction(function () use ($order, $productDiscountId) {
            DB::table('order_product_discount_allocations')
                ->where('product_discount_id', $productDiscountId)
                ->where('order_id', $order->id)
                ->whereNull('reversed_at')
                ->update(['reversed_at' => now(), 'updated_at' => now()]);

            self::refreshLineTotals($order);
        });
    }

    /** Σ active allocations for an order, in cents. */
    public static function allocatedCents(Order $order): int
    {
        $sum = DB::table('order_product_discount_allocations')
            ->where('order_id', $order->id)
            ->whereNull('reversed_at')
            ->sum('allocated_amount');

        return (int) round(((float) $sum) * 100);
    }

    /** The portion of an order's discount that predates allocation tracking, in cents. */
    public static function legacyUnallocatedCents(Order $order): int
    {
        return (int) round(((float) ($order->legacy_unallocated_pretax_discount ?? 0)) * 100);
    }

    /**
     * Does the allocation ledger agree with the order's own total?
     *
     * THE IDENTITY:
     *
     *     legacy_unallocated_pretax_discount
     *   + Σ active tracked allocations
     *   = orders.pretax_discount_total
     *
     * The legacy term is not a fudge factor — it is the explicitly recorded
     * amount that was conceded before per-line allocation existed. Without it,
     * adding a NEW adjustment to such an order would compare only the new rows
     * against the ENTIRE total and refuse legitimate work; and comparing the
     * new rows against the total MINUS whatever happened to be missing would
     * be worse still, because the new allocations would silently appear to
     * account for a historical concession they know nothing about.
     *
     * Reconciliation therefore constrains only the TRACKED portion — the part
     * this service actually controls. The legacy portion stays visible at
     * order level and is never absorbed into a line.
     *
     * Returns null when the identity holds, or a description when it does not.
     */
    public static function reconcile(Order $order): ?string
    {
        // An order with no lines has no population to allocate across —
        // extension children are built this way by design, and so are several
        // order shapes that carry totals without itemization. There is nothing
        // for allocations to constrain, so there is nothing to report. This is
        // deliberately narrow: an order that HAS lines is always constrained.
        if (self::lines($order)->isEmpty()) {
            return null;
        }

        $total = (int) round(((float) $order->pretax_discount_total) * 100);
        $legacy = self::legacyUnallocatedCents($order);
        $tracked = self::allocatedCents($order);

        if ($legacy + $tracked === $total) {
            return null;
        }

        if ($legacy > 0) {
            return "Order {$order->id}: legacy {$legacy}c + tracked {$tracked}c does not equal "
                ."pretax_discount_total {$total}c.";
        }

        return "Order {$order->id}: allocations total {$tracked}c but pretax_discount_total is {$total}c.";
    }

    // ── internals ──────────────────────────────────────────────────────────

    /**
     * Deterministic largest-remainder distribution.
     *
     * @return array<int,int> order_product_id => cents, in ascending id order
     */
    private static function distribute($lines, int $amountCents): array
    {
        $c = static fn ($v): int => (int) round(((float) $v) * 100);

        $weights = [];
        $totalWeight = 0;

        foreach ($lines as $line) {
            $w = max(0, $c($line->sub_total));
            $weights[(int) $line->id] = $w;
            $totalWeight += $w;
        }

        // Nothing to weight by — spread evenly, still deterministically.
        if ($totalWeight <= 0) {
            $ids = array_keys($weights);
            $each = intdiv($amountCents, count($ids));
            $shares = array_fill_keys($ids, $each);
            $shares[$ids[count($ids) - 1]] += $amountCents - ($each * count($ids));

            return $shares;
        }

        $shares = [];
        $remainders = [];
        $assigned = 0;

        foreach ($weights as $id => $w) {
            $exact = $amountCents * $w / $totalWeight;
            $shares[$id] = (int) floor($exact);
            $remainders[$id] = $exact - $shares[$id];
            $assigned += $shares[$id];
        }

        // Largest remainder first; ties fall to the LOWER line id, never to
        // whatever order the rows happened to arrive in.
        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders) {
            if ($remainders[$a] === $remainders[$b]) {
                return $a <=> $b;
            }

            return $remainders[$b] <=> $remainders[$a];
        });

        $leftover = $amountCents - $assigned;

        foreach ($order as $id) {
            if ($leftover <= 0) {
                break;
            }

            $shares[$id]++;
            $leftover--;
        }

        ksort($shares);

        return $shares;
    }

    /** Recompute each line's denormalized active-allocation sum. */
    private static function refreshLineTotals(Order $order): void
    {
        $sums = DB::table('order_product_discount_allocations')
            ->select('order_product_id', DB::raw('SUM(allocated_amount) AS total'))
            ->where('order_id', $order->id)
            ->whereNull('reversed_at')
            ->groupBy('order_product_id')
            ->pluck('total', 'order_product_id');

        foreach (self::lines($order) as $line) {
            OrderProduct::where('id', $line->id)
                ->update(['pretax_discount_allocated' => (float) ($sums[$line->id] ?? 0)]);
        }
    }

    private static function lines(Order $order)
    {
        return OrderProduct::where('order_id', $order->id)
            ->orderBy('id')
            ->get(['id', 'sub_total']);
    }
}
