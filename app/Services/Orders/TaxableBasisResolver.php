<?php

namespace App\Services\Orders;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;

/**
 * What merchandise value actually generated an order's `tax_amount`?
 *
 * ── WHY THIS EXISTS ───────────────────────────────────────────────────────
 *
 * Refunds need an effective tax rate to split a tax-inclusive amount into base
 * and tax. That rate is `tax_amount ÷ taxable basis`, and the whole difficulty
 * is the denominator: **`orders.subtotal` is not it.**
 *
 * `subtotal` is GROSS merchandise across every line. It overstates the taxable
 * basis in two independent ways:
 *
 *   1. TAX-FREE LINES. `CartHelper::buildCartItem()` zeroes a line's tax when
 *      the product is `is_tax_free_item`, but `subtotal` still accumulates that
 *      line. On $100 taxable at 9.75% plus $100 tax-free, `tax_amount/subtotal`
 *      yields 4.875% — half the real rate, and plausible enough to go unnoticed
 *      for a long time.
 *
 *   2. PRE-TAX ADJUSTMENTS. A Store Credit or Goodwill concession reduces
 *      `tax_amount` while `subtotal` stays gross, so the numerator shrinks
 *      against an unchanged denominator.
 *
 * Both understate the rate, so the tax portion of every affected refund has
 * been too small. The customer's total refund was always correct; its tax/base
 * split was not, which is a tax-remittance problem rather than a customer one.
 *
 * ── HOW THE BASIS IS RECOVERED ────────────────────────────────────────────
 *
 * For an order with lines:
 *
 *     taxable_basis = taxable_gross × (subtotal − pretax_discount_total) ÷ subtotal
 *
 * where `taxable_gross` is the summed gross value of the lines that were
 * ORIGINALLY taxable, read from each line's frozen `product_data` snapshot.
 *
 * This is not an approximation — it is the exact inverse of how the engine
 * computes the tax it stored. `OrderDiscountTarget` reduces tax by the
 * surviving fraction of the whole merchandise population:
 *
 *     new_tax = base_tax × remaining ÷ subtotal
 *
 * and the taxable subset shrinks by the same fraction, so
 *
 *     new_tax ÷ (taxable_gross × remaining ÷ subtotal) = base_tax ÷ taxable_gross
 *
 * which is the original rate, recovered exactly. Using the order-level
 * `pretax_discount_total` rather than per-line allocations means legacy
 * concessions — applied before allocation tracking existed — resolve by the
 * same formula, with no special case and nothing invented.
 *
 * ── THE ONE ASSUMPTION, STATED ────────────────────────────────────────────
 *
 * This holds while every pre-tax adjustment reduces the whole merchandise
 * population proportionally, which is exactly what `PretaxDiscountAllocator`
 * does today. It is the same assumption the engine's own tax computation makes,
 * so the two cannot drift apart: a line-targeted adjustment would invalidate
 * both at once, and both are documented as requiring per-line derivation at
 * that point.
 *
 * ── LINE-LESS ORDERS ──────────────────────────────────────────────────────
 *
 * An EXTENSION CHILD carries no lines by design. Its `subtotal` is already the
 * discounted taxable base — `Extension\StoreController` writes the post-discount
 * `discountedProductValue` there and the tax computed on exactly that figure —
 * so for this shape, and only this shape, `subtotal` IS the taxable basis. That
 * is explicit handling of a known construction, not a fallback.
 *
 * Any other line-less order is UNRESOLVABLE and returns null. There is
 * deliberately no generic `tax_amount ÷ subtotal` fallback: falling back to the
 * known-wrong denominator is what this class exists to remove, and a
 * plausible-looking wrong rate is worse than an admitted absence.
 *
 * Read-only. Computes nothing that is stored, writes nothing, and never refuses
 * an operation — callers decide what an unresolvable basis means for them.
 */
final class TaxableBasisResolver
{
    /**
     * The taxable merchandise basis behind `orders.tax_amount`, in integer
     * cents — or null when it cannot be determined from the record.
     *
     * Returns 0 when no tax was charged: there is no basis to find, and the
     * answer is not "unknown".
     */
    public static function resolveCents(Order $order): ?int
    {
        $taxCents = self::cents($order->tax_amount);

        if ($taxCents <= 0) {
            return 0;
        }

        $lines = OrderProduct::where('order_id', $order->id)
            ->get(['id', 'sub_total', 'tax', 'product_data']);

        if ($lines->isNotEmpty()) {
            return self::fromLines($order, $lines);
        }

        // The one line-less shape whose subtotal is, by construction, already
        // the discounted taxable base.
        if ($order->extensionCharge()->exists()) {
            return self::cents($order->subtotal);
        }

        return null;
    }

    /**
     * The effective rate `tax_amount ÷ taxable basis`, or null when the basis
     * cannot be determined. 0.0 when no tax was charged.
     */
    public static function effectiveTaxRate(Order $order): ?float
    {
        $basisCents = self::resolveCents($order);

        if ($basisCents === null) {
            return null;
        }

        if ($basisCents === 0) {
            return 0.0;
        }

        return self::cents($order->tax_amount) / $basisCents;
    }

    // ── internals ──────────────────────────────────────────────────────────

    private static function fromLines(Order $order, $lines): ?int
    {
        $subtotalCents = self::cents($order->subtotal);

        if ($subtotalCents <= 0) {
            return null;
        }

        $taxableGrossCents = 0;

        foreach ($lines as $line) {
            if (self::wasTaxable($line)) {
                $taxableGrossCents += self::cents($line->sub_total);
            }
        }

        // Tax was charged, yet no line looks taxable. The record contradicts
        // itself; inventing a basis would launder that contradiction into a
        // number a report would treat as authoritative.
        if ($taxableGrossCents <= 0) {
            return null;
        }

        $remainingCents = $subtotalCents - self::cents($order->pretax_discount_total);

        if ($remainingCents <= 0) {
            return null;
        }

        return (int) round($taxableGrossCents * $remainingCents / $subtotalCents);
    }

    /**
     * Was this line taxable at checkout?
     *
     * Read from the FROZEN snapshot, not the live column. A pre-tax adjustment
     * scales a line's current tax down, and on a small line that can round to
     * zero — which would make a genuinely taxable line look exempt and shrink
     * the basis. The snapshot cannot move.
     *
     * A legacy line with no readable snapshot falls back to its current tax.
     * That is the best available evidence for such a line, and it is a narrower
     * inference than assuming the whole subtotal was taxable.
     */
    private static function wasTaxable(OrderProduct $line): bool
    {
        $frozen = is_array($line->product_data) ? $line->product_data : null;

        if ($frozen !== null && array_key_exists('tax', $frozen)) {
            return self::cents($frozen['tax']) > 0;
        }

        return self::cents($line->tax) > 0;
    }

    private static function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
