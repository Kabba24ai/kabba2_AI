<?php

namespace App\Services\Orders;

use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Http\DataObjects\HistoricalTaxBasis;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\Schema;

/**
 * Reconstructs the historical tax basis an order was ORIGINALLY taxed on.
 *
 * This is a focused financial reconstruction service. It is deliberately NOT
 * a generic pre-tax-adjustment abstraction — it answers exactly one question
 * ("what basis and rate produced this order's stored tax?") for exactly two
 * callers: {@see GoodwillAdjustmentService} and
 * {@see PaymentAllocationService::proportionalTaxRefund()}. One resolver,
 * two consumers, so the adjustment path and the refund path can never drift
 * onto different denominators for the same order.
 *
 * WHY THIS EXISTS. `orders.subtotal` is not the taxable basis.
 * `CartHelper::buildCartItem()` zeroes a line's tax when the product is
 * flagged `is_tax_free_item`, but `orders.subtotal` still accumulates that
 * line. Dividing `orders.tax_amount` by `orders.subtotal` therefore
 * understates the rate on any mixed order — a $200 order half of it tax-free
 * at 9.75% derives 4.875%, a plausible-looking number that is simply wrong.
 * That division is live in `PaymentAllocationService` today; this class is
 * what replaces it.
 *
 * GUARANTEES:
 *  - READ-ONLY. Never writes, never saves, never normalizes legacy data
 *    while resolving. Resolving an order twice cannot change it.
 *  - DETERMINISTIC. Same stored rows in, same result out.
 *  - FROZEN DATA ONLY. Reads `orders` columns and `order_products` columns
 *    plus each line's frozen `product_data` snapshot. It never consults the
 *    `products` table, store configuration, or any tax setting — today's
 *    configuration says nothing about what an order was taxed at last March.
 *  - EXACT RECONCILIATION. Line sums must match stored order totals to the
 *    cent. No tolerance, no epsilon.
 *  - NO GUESSED FALLBACK. Every unsupported or corrupt state returns a named
 *    {@see HistoricalTaxBasisFailure}. There is no "best effort" path.
 */
class HistoricalTaxBasisResolver
{
    /** Memoized per process — Schema::hasTable() is a metadata query and the answer cannot change mid-request. */
    private static ?bool $goodwillTableExists = null;

    public static function resolve(Order $order): HistoricalTaxBasis
    {
        if (self::hasActiveGoodwillAdjustment($order)) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::AmbiguousExistingAdjustment);
        }

        $lines = $order->products()->orderBy('id')->get();

        if ($lines->isEmpty()) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::NoLineData);
        }

        $storedSubtotalCents   = self::toCents($order->subtotal);
        $storedTaxCents        = self::toCents($order->tax_amount);
        $storedDiscountCents   = self::toCents($order->discount_amount);
        $storedGrandTotalCents = self::toCents($order->grand_total);

        $ordinaryBasisCents = 0;
        $ordinaryTaxCents   = 0;
        $nonTaxableCents    = 0;
        $specialBasisCents  = 0;
        $specialTaxCents    = 0;
        $addedFeesCents     = 0;
        $sumBasisCents      = 0;
        $resolvedLines      = [];

        foreach ($lines as $line) {
            $basisCents = self::toCents($line->sub_total);
            $taxCents   = self::toCents($line->tax);

            // Tax recorded against no basis cannot be derived from anything.
            if ($taxCents !== 0 && $basisCents <= 0) {
                return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::UnexplainedTaxOverride);
            }

            $frozen = self::frozenData($line);
            if ($frozen === null) {
                return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::FrozenDataUnavailable);
            }

            $lineSpecialTaxCents = self::toCents($frozen['special_tax'] ?? 0);
            $lineAddedFeesCents  = self::toCents($frozen['added_fees'] ?? 0);

            $taxable = $taxCents > 0;

            if ($taxable) {
                $ordinaryBasisCents += $basisCents;
                $ordinaryTaxCents   += $taxCents;
            } else {
                $nonTaxableCents += $basisCents;
            }

            if ($lineSpecialTaxCents > 0) {
                $specialBasisCents += $basisCents;
                $specialTaxCents   += $lineSpecialTaxCents;
            }

            $addedFeesCents += $lineAddedFeesCents;
            $sumBasisCents  += $basisCents;

            $resolvedLines[] = [
                'id'          => (int) $line->id,
                'basis_cents' => $basisCents,
                'tax_cents'   => $taxCents,
                'taxable'     => $taxable,
            ];
        }

        // ── Exact reconciliation against stored totals ──────────────────────
        if ($sumBasisCents !== $storedSubtotalCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::UnreconciledSubtotal);
        }

        if ($ordinaryTaxCents !== $storedTaxCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::UnreconciledTax);
        }

        if ($ordinaryBasisCents <= 0 && $storedTaxCents !== 0) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ZeroBasisWithTax);
        }

        // grand_total = subtotal + ordinary tax + special tax + added fees - discount
        $expectedGrandTotalCents = $storedSubtotalCents
            + $ordinaryTaxCents
            + $specialTaxCents
            + $addedFeesCents
            - $storedDiscountCents;

        if ($expectedGrandTotalCents !== $storedGrandTotalCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::UnreconciledGrandTotal);
        }

        if (! self::singleRateExplainsEveryLine($resolvedLines, $ordinaryBasisCents, $ordinaryTaxCents)) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::MixedTaxRates);
        }

        return HistoricalTaxBasis::resolved(
            ordinaryBasisCents:   $ordinaryBasisCents,
            ordinaryTaxCents:     $ordinaryTaxCents,
            specialBasisCents:    $specialBasisCents,
            specialTaxCents:      $specialTaxCents,
            nonTaxableBasisCents: $nonTaxableCents,
            addedFeesCents:       $addedFeesCents,
            discountCents:        $storedDiscountCents,
            lines:                $resolvedLines,
        );
    }

    /**
     * Does ONE rate reproduce every taxable line's stored tax?
     *
     * Per-line rates are compared by reconstruction rather than by dividing
     * each line and comparing quotients, because each stored line tax was
     * itself rounded to the cent — two lines at the identical rate routinely
     * produce slightly different quotients. A one-cent reconstruction
     * difference is therefore accepted as rounding; anything larger means the
     * lines genuinely were not taxed at the same rate, and no single
     * historical rate exists to derive.
     *
     * @param  array<int, array{id:int, basis_cents:int, tax_cents:int, taxable:bool}>  $lines
     */
    private static function singleRateExplainsEveryLine(array $lines, int $basisCents, int $taxCents): bool
    {
        if ($basisCents <= 0) {
            return true; // Nothing taxable; the zero-basis guard above owns this case.
        }

        $rate = $taxCents / $basisCents;

        foreach ($lines as $line) {
            if (! $line['taxable']) {
                continue;
            }

            $expected = (int) round($line['basis_cents'] * $rate);

            if (abs($expected - $line['tax_cents']) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * The line's frozen cart snapshot, or null if it cannot be read.
     *
     * `order_products.product_data` is the JSON written at order creation and
     * is the ONLY record of special tax and added fees — neither has a
     * column on any table. Returning null here (rather than defaulting to
     * zero) is deliberate: absent data means we cannot prove those buckets
     * were zero, and proving is the whole job.
     *
     * @return array<string, mixed>|null
     */
    private static function frozenData($line): ?array
    {
        $raw = $line->product_data;

        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function hasActiveGoodwillAdjustment(Order $order): bool
    {
        if (self::$goodwillTableExists === null) {
            self::$goodwillTableExists = Schema::hasTable('order_goodwill_adjustments');
        }

        if (self::$goodwillTableExists !== true) {
            return false;
        }

        return \DB::table('order_goodwill_adjustments')
            ->where('order_id', $order->id)
            ->whereNull('reversed_at')
            ->exists();
    }

    /**
     * decimal(10,2) storage → integer cents.
     *
     * The float cast is confined to this one line and immediately
     * integerized. Values arrive from DECIMAL(10,2) columns or from the
     * frozen JSON, both of which carry at most two decimal places well
     * inside float's exact-integer range once scaled.
     */
    private static function toCents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    /** Test seam — resets the memoized schema probe between migrations. */
    public static function flushSchemaMemo(): void
    {
        self::$goodwillTableExists = null;
    }
}
