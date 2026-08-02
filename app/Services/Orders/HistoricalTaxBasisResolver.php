<?php

namespace App\Services\Orders;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Enums\Orders\HistoricalTaxBasisSource;
use App\Http\DataObjects\HistoricalTaxBasis;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
 *  - ORDER-LOCAL DATA ONLY. Reads `orders` columns and `order_products`
 *    columns plus each line's frozen `product_data` snapshot. It never
 *    consults the `products` table, store configuration, or any tax setting —
 *    today's configuration says nothing about what an order was taxed at last
 *    March.
 *
 *    Money comes from COLUMNS; `product_data` is consulted only for what a
 *    line IS (its classification), never for what it currently COSTS. Special
 *    tax and added fees moved to columns for exactly this reason: they are
 *    live financial state that a Goodwill Adjustment may revise, and sourcing
 *    them from an immutable snapshot pinned them to their original values
 *    while the order's grand total moved, breaking reconciliation forever
 *    after. The snapshot remains the original-state record for audit.
 *  - EXACT RECONCILIATION. Line sums must match stored order totals to the
 *    cent. No tolerance, no epsilon.
 *  - NO GUESSED FALLBACK. Every unsupported or corrupt state returns a named
 *    {@see HistoricalTaxBasisFailure}. There is no "best effort" path.
 */
class HistoricalTaxBasisResolver
{
    /**
     * Frozen `product_type` values that represent a protected fee rather than
     * merchandise. Empty today by design — see isProtectedFeeLine().
     *
     * @var list<string>
     */
    private const PROTECTED_FEE_PRODUCT_TYPES = [];

    /**
     * Reconstruct the basis from the order's CURRENT stored state.
     *
     * There is deliberately NO "refuse if an adjustment exists" guard here.
     * An earlier revision had one, and it was wrong in a way only a
     * whole-lifecycle walk exposed: this resolver serves BOTH Goodwill and
     * refunds, so refusing an adjusted order made every Goodwill adjustment
     * silently render its order permanently un-refundable — a far worse
     * defect than the stacking it was trying to prevent.
     *
     * The guard was also in the wrong place. "Do not stack a second
     * adjustment" is Goodwill's rule, and {@see GoodwillAdjustmentService::
     * apply()} already enforces it under the order row lock, which is the
     * only place it can be enforced race-free. A read-only reconstruction
     * cannot hold a lock and so could never have been the real defence.
     *
     * An adjusted order is fully reconstructable: apply() rewrites the order
     * columns and every line together, and the reconciliation identity is
     * asserted in integer cents before anything is written. The stored state
     * afterwards is exactly as self-consistent as it was before — which is
     * the property this resolver actually depends on.
     */
    public static function resolve(Order $order): HistoricalTaxBasis
    {
        $lines = $order->products()->orderBy('id')->get();

        if ($lines->isEmpty()) {
            return self::logSource($order, self::resolveLineless($order));
        }

        $storedSubtotalCents   = self::toCents($order->subtotal);
        $storedTaxCents        = self::toCents($order->tax_amount);
        $storedDiscountCents   = self::toCents($order->discount_amount);
        $storedGrandTotalCents = self::toCents($order->grand_total);

        $ordinaryBasisCents = 0;
        $ordinaryTaxCents   = 0;
        $untaxedMerchCents  = 0;
        $protectedLineCents = 0;
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

            // CURRENT columns, not the frozen snapshot. `product_data` remains
            // the immutable original and is still what proves a line's
            // classification below — but special tax and added fees are live
            // financial state that a Goodwill Adjustment may legitimately
            // revise, exactly like sub_total and tax immediately above. Reading
            // them from JSON would pin them to their original values while the
            // order's grand total moved, permanently breaking the
            // reconciliation identity asserted further down.
            $lineSpecialTaxCents = self::toCents($line->special_tax);
            $lineAddedFeesCents  = self::toCents($line->added_fees);

            $taxable = $taxCents > 0;

            // Merchandise vs protected fee. A line is merchandise unless its
            // FROZEN data proves otherwise — carrying no tax does not make a
            // line a fee, it only makes it untaxed merchandise, and untaxed
            // merchandise is reducible (FD-002 Amendment 3).
            $protectedLine = self::isProtectedFeeLine($frozen);

            if ($protectedLine) {
                $protectedLineCents += $basisCents;
            } elseif ($taxable) {
                $ordinaryBasisCents += $basisCents;
                $ordinaryTaxCents   += $taxCents;
            } else {
                $untaxedMerchCents += $basisCents;
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
                // Carried per line so a Goodwill Adjustment can reduce special
                // tax at its own rate on exactly the lines that bear it, and
                // so a reversal restores each line's own figure rather than
                // redistributing an order-level total.
                'special_tax_cents' => $lineSpecialTaxCents,
                'added_fees_cents'  => $lineAddedFeesCents,
                'taxable'     => $taxable && ! $protectedLine,
                // Reducible merchandise — taxable or not. Goodwill allocates
                // across these; protected lines never receive a share.
                'reducible'   => ! $protectedLine,
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

        return self::logSource($order, HistoricalTaxBasis::resolved(
            ordinaryBasisCents:   $ordinaryBasisCents,
            ordinaryTaxCents:     $ordinaryTaxCents,
            specialBasisCents:    $specialBasisCents,
            specialTaxCents:      $specialTaxCents,
            untaxedMerchandiseBasisCents: $untaxedMerchCents,
            addedFeesCents:       $addedFeesCents,
            discountCents:        $storedDiscountCents,
            lines:                $resolvedLines,
            source:               HistoricalTaxBasisSource::OrderProductLines,
            protectedLineCents:   $protectedLineCents,
        ));
    }

    /**
     * An order with no order_products rows.
     *
     * Extension child orders own no lines BY DESIGN — `Extension\StoreController`
     * creates them with totals only, and `IndexController` documents the same
     * fact. They are nevertheless real, independently payable orders (they get
     * their own Pending COD placeholder payment) that render on the ordinary
     * Order Details page, so they can and do reach the Standard refund path.
     *
     * Two named modes handle them, in strict priority. Everything else — any
     * line-less order that cannot PROVE it is a simple extension child — is
     * rejected. There is deliberately no general line-less fallback, because a
     * line-less order of unknown shape could hide a tax-free component inside
     * its subtotal, which is the very defect this class exists to prevent.
     */
    private static function resolveLineless(Order $order): HistoricalTaxBasis
    {
        if (! self::isExtensionChild($order)) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::NoLineData);
        }

        $charges = DB::table('billing_charges')
            ->where('child_order_id', $order->id)
            ->where('billing_charge_type', BillingChargeType::Extension->value)
            ->whereNull('deleted_at')
            ->get();

        if ($charges->count() > 1) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ConflictingExtensionCharges);
        }

        if ($charges->count() === 1) {
            // Authoritative record found. Its verdict is final: if it
            // contradicts the order we reject outright rather than falling
            // through to the weaker order-level mode. Contradictory
            // authoritative data is a reason to stop, not to try again with
            // less evidence.
            return self::resolveFromExtensionCharge($order, $charges->first());
        }

        return self::resolveExtensionOrderLevel($order);
    }

    /**
     * MODE 1 (preferred) — reconstruct from the linked extension charge.
     *
     * `billing_charges` carries the extension's own frozen `amount`,
     * `tax_amount` and `tax_type`, written by the BillingEngine bridge at
     * creation time. That is genuine authoritative evidence, not inference.
     */
    private static function resolveFromExtensionCharge(Order $order, $charge): HistoricalTaxBasis
    {
        $chargeBasisCents = self::toCents($charge->amount);
        $chargeTaxCents   = self::toCents($charge->tax_amount);
        $taxType          = (string) ($charge->tax_type ?? '');

        $storedSubtotalCents   = self::toCents($order->subtotal);
        $storedTaxCents        = self::toCents($order->tax_amount);
        $storedDiscountCents   = self::toCents($order->discount_amount);
        $storedGrandTotalCents = self::toCents($order->grand_total);

        // tax_type must state the posture unambiguously — 'add' (taxable) or
        // 'free' (tax-free). Anything else leaves taxability unestablished.
        if (! in_array($taxType, ['add', 'free'], true)) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionChargeMismatch);
        }

        if ($taxType === 'free' && $chargeTaxCents !== 0) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionChargeMismatch);
        }

        // The charge must agree with the child order it points at, exactly.
        if ($chargeBasisCents !== $storedSubtotalCents || $chargeTaxCents !== $storedTaxCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionChargeMismatch);
        }

        if ($storedSubtotalCents + $storedTaxCents - $storedDiscountCents !== $storedGrandTotalCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionChargeMismatch);
        }

        $taxable = $taxType === 'add' && $chargeTaxCents > 0;

        return HistoricalTaxBasis::resolved(
            ordinaryBasisCents:   $taxable ? $chargeBasisCents : 0,
            ordinaryTaxCents:     $chargeTaxCents,
            specialBasisCents:    0,
            specialTaxCents:      0,
            untaxedMerchandiseBasisCents: $taxable ? 0 : $chargeBasisCents,
            addedFeesCents:       0,
            discountCents:        $storedDiscountCents,
            lines:                [],
            source:               HistoricalTaxBasisSource::ExtensionBillingCharge,
        );
    }

    /**
     * MODE 2 (fallback) — the extension child's own stored totals.
     *
     * Reached ONLY when no linked charge exists at all: a legacy extension
     * predating the bridge, or one whose BillingEngine call failed — that
     * bridge is wrapped in a try/catch that only logs, so a missing charge is
     * a real, expected shape rather than a theoretical one.
     *
     * This is emphatically NOT "use orders.subtotal when unsure". It is
     * permitted only once every extension invariant below is PROVEN, and the
     * reason it is sound is narrow and specific: an extension is created from
     * a single `add_tax` flag applied to a single base amount, so the whole
     * order has exactly one tax posture. No tax-free component can be hiding
     * inside its subtotal, which is the only thing that made the old
     * `tax_amount / subtotal` denominator wrong. Any order that cannot prove
     * that property is rejected.
     */
    private static function resolveExtensionOrderLevel(Order $order): HistoricalTaxBasis
    {
        $subtotalCents   = self::toCents($order->subtotal);
        $taxCents        = self::toCents($order->tax_amount);
        $discountCents   = self::toCents($order->discount_amount);
        $grandTotalCents = self::toCents($order->grand_total);

        // Invariant: no discount may sit between the basis and the total, or
        // the denominator's relationship to the stored figures is ambiguous.
        if ($discountCents !== 0) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven);
        }

        // Invariant: stored totals reconcile exactly, with no room for an
        // unaccounted special tax or added fee hiding in the difference.
        if ($subtotalCents + $taxCents !== $grandTotalCents) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven);
        }

        // Invariant: a basis must exist to divide by whenever tax was charged.
        if ($taxCents !== 0 && $subtotalCents <= 0) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ZeroBasisWithTax);
        }

        // Invariant: the order's single tax posture must agree with its stored
        // tax. `is_tax_exempt` governs the WHOLE extension — a tax-exempt
        // extension carrying tax, or a taxable one carrying none, is not the
        // simple shape this mode is allowed to assume.
        $exempt = (string) ($order->is_tax_exempt ?? '') === 'Yes';

        if ($exempt && $taxCents !== 0) {
            return HistoricalTaxBasis::failed(HistoricalTaxBasisFailure::ExtensionInvariantsUnproven);
        }

        $taxable = ! $exempt && $taxCents > 0;

        return HistoricalTaxBasis::resolved(
            ordinaryBasisCents:   $taxable ? $subtotalCents : 0,
            ordinaryTaxCents:     $taxCents,
            specialBasisCents:    0,
            specialTaxCents:      0,
            untaxedMerchandiseBasisCents: $taxable ? 0 : $subtotalCents,
            addedFeesCents:       0,
            discountCents:        0,
            lines:                [],
            source:               HistoricalTaxBasisSource::ExtensionOrderLevel,
        );
    }

    /**
     * An extension child, as the codebase already defines one.
     *
     * Reuses Order::scopeExtensionChildren()'s rule rather than inventing a
     * second definition: a reference_order_number plus an order_number of the
     * form "<parent>-A". Reorders also carry a reference_order_number but have
     * their own products, so they never reach this path anyway.
     */
    private static function isExtensionChild(Order $order): bool
    {
        return Order::query()->whereKey($order->id)->extensionChildren()->exists();
    }

    /**
     * Record which evidence a resolution used, so any refund's tax split can
     * be traced back to it. Weaker sources are logged at a higher level than
     * the ordinary one precisely so they stay visible rather than becoming
     * an invisible default.
     */
    private static function logSource(Order $order, HistoricalTaxBasis $basis): HistoricalTaxBasis
    {
        $context = [
            'order_id' => $order->id,
            'source'   => $basis->source?->value,
            'failure'  => $basis->failure?->value,
        ];

        if (! $basis->succeeded()) {
            Log::info('HistoricalTaxBasisResolver: resolution rejected', $context);
        } elseif ($basis->source === HistoricalTaxBasisSource::ExtensionOrderLevel) {
            Log::warning('HistoricalTaxBasisResolver: resolved from extension order-level totals (no linked charge)', $context);
        } else {
            Log::debug('HistoricalTaxBasisResolver: resolved', $context);
        }

        return $basis;
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
     * Does this line's FROZEN data prove it is a fee-type component rather
     * than merchandise?
     *
     * Deliberately conservative: merchandise is the default, and only positive
     * evidence in the frozen snapshot moves a line into the protected bucket.
     * A tax-free product is NOT such evidence — it is untaxed merchandise, and
     * reducible (FD-002 Amendment 3).
     *
     * Currently this can never be true: the frozen `product_type` is only
     * 'Rental' or 'Retail' in this schema, both merchandise. The check exists
     * so the rule is stated in advance rather than improvised if a fee-type
     * product is ever introduced. Current product or store configuration is
     * never consulted — only the frozen line.
     *
     * @param  array<string,mixed>  $frozen
     */
    private static function isProtectedFeeLine(array $frozen): bool
    {
        $type = strtolower(trim((string) ($frozen['product_type'] ?? '')));

        return in_array($type, self::PROTECTED_FEE_PRODUCT_TYPES, true);
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

    /**
     * Test seam, retained deliberately.
     *
     * This class memoized a Schema::hasTable() probe while it carried an
     * active-adjustment guard. That guard is gone (see resolve()), so there is
     * nothing left to flush — but the seam is kept as a no-op so suites that
     * migrate mid-run keep a single, obvious place to reset process state if a
     * memo is ever reintroduced.
     */
    public static function flushSchemaMemo(): void
    {
        // No memoized state today.
    }
}
