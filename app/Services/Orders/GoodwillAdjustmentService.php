<?php

namespace App\Services\Orders;

use App\Http\DataObjects\GoodwillCalculation;
use App\Http\DataObjects\GoodwillCalculationFailure;
use App\Http\DataObjects\HistoricalTaxBasis;
use App\Models\Orders\Order;
use App\Services\TaxCalculationService;

/**
 * Goodwill Adjustment — calculation (FD-002, Truth Table type 17).
 *
 * A Goodwill Adjustment is a manager-authorised, discretionary PRE-TAX
 * reduction of an order's taxable basis, applied so the revised grand total
 * equals the cumulative settled payments accepted as payment in full. It is
 * not tender: it never creates an order_payments row and never appears as a
 * payment method. The money actually received is recorded through the real
 * tender the customer used.
 *
 * MONETARY PRECISION (FD-002). Every value here is integer cents.
 * TaxCalculationService is the ONLY authorised calculation boundary — cents
 * are converted to float dollars solely for that one call and normalised
 * straight back. The reconciliation identity is asserted in INTEGERS:
 *
 *     revisedBasis + nonTaxable + revisedTax + revisedSpecialTax
 *         + addedFees - discount  ===  acceptedAmount
 *
 * If it does not hold exactly, the calculation fails and nothing is written.
 * No parallel tax calculator exists, and no float epsilon is used anywhere.
 *
 * TWO TAXES, NEVER BLENDED (FD-002 Amendment 1 §2). Ordinary sales tax and
 * special tax are levied at different rates on the same basis. Both are
 * recomputed on the reduced basis, each at its OWN rate. The combined rate is
 * used only to solve the inclusive→exclusive extraction in one call; the two
 * taxes are then derived separately and stored separately.
 *
 * preview() and apply() share this one calculation path, so the figures an
 * approving manager sees cannot differ from the figures committed.
 */
class GoodwillAdjustmentService
{
    /**
     * Compute the adjustment implied by accepting `$acceptedCents` as payment
     * in full. Read-only: touches nothing, writes nothing.
     *
     * @param  int  $acceptedCents  Cumulative settled payments AFTER the payment
     *         being recorded now — never the newest payment alone (FD-002 §2).
     */
    public static function calculate(Order $order, int $acceptedCents): GoodwillCalculation
    {
        $basis = HistoricalTaxBasisResolver::resolve($order);

        if (! $basis->succeeded()) {
            return GoodwillCalculation::failed(
                GoodwillCalculationFailure::BasisUnreconstructable,
                $basis->failure,
            );
        }

        if ($acceptedCents <= 0) {
            // A $0.00 concession is a write-off, not Goodwill. Truth Table
            // §5.9 leaves that undecided, so it is refused rather than
            // silently treated as a 100% adjustment.
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NoPaymentReceived);
        }

        $originalBasisCents      = $basis->ordinaryBasisCents;
        $originalUntaxedCents    = $basis->untaxedMerchandiseBasisCents;
        $originalTaxCents        = $basis->ordinaryTaxCents;
        $originalSpecialTaxCents = $basis->specialTaxCents;
        $protectedCents          = $basis->protectedCents();
        $discountCents           = $basis->discountCents;

        // Everything Goodwill may reduce: taxable AND untaxed merchandise.
        // Tax-exempt merchandise is still merchandise (FD-002 Amendment 3).
        $merchandiseCents = $originalBasisCents + $originalUntaxedCents;

        $originalGrandTotalCents = $merchandiseCents
            + $originalTaxCents
            + $originalSpecialTaxCents
            + $protectedCents
            - $discountCents;

        if ($acceptedCents >= $originalGrandTotalCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        if ($merchandiseCents <= 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        // Protected components and the discount sit outside the reduction.
        $absorbableCents = $acceptedCents - $protectedCents + $discountCents;

        if ($absorbableCents < 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::PaymentBelowNonReducibleFloor);
        }

        $ordinaryRate = $basis->ordinaryRate();
        $specialRate  = $basis->specialRate();

        // Reducing merchandise proportionally keeps each bucket's share of the
        // basis constant, so the tax the revised merchandise attracts is the
        // basis times a blended rate weighted by how much of the merchandise
        // was taxable at all. For a fully tax-exempt order both weights are
        // zero, the blended rate is zero, and the revised merchandise simply
        // equals the accepted amount — which is exactly the intended
        // $200 -> $185 with $15 waived and no tax anywhere.
        $effectiveRate = ($originalBasisCents / $merchandiseCents) * $ordinaryRate
            + ($basis->specialBasisCents / $merchandiseCents) * $specialRate;

        // ONE call to the authorised boundary.
        $breakdown = TaxCalculationService::extractTaxFromInclusiveAmount(
            $absorbableCents / 100,
            $effectiveRate,
        );

        $revisedMerchandiseCents = (int) round($breakdown->baseAmount * 100);

        if ($revisedMerchandiseCents < 0 || $revisedMerchandiseCents > $merchandiseCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        // Split the revised merchandise back into its two buckets in the same
        // proportion they held before, with the residual cent to the taxable
        // side so the identity closes.
        $revisedBasisCents   = $originalBasisCents > 0
            ? (int) round($revisedMerchandiseCents * $originalBasisCents / $merchandiseCents)
            : 0;
        $revisedUntaxedCents = $revisedMerchandiseCents - $revisedBasisCents;

        // Total tax is the remainder by construction; special tax is derived at
        // its own rate and the residual cent falls to ordinary tax. The two are
        // never reported as one blended figure.
        $totalRevisedTaxCents   = $absorbableCents - $revisedMerchandiseCents;
        $revisedSpecialBasis    = $merchandiseCents > 0
            ? (int) round($revisedMerchandiseCents * $basis->specialBasisCents / $merchandiseCents)
            : 0;
        $revisedSpecialTaxCents = $specialRate > 0 ? (int) round($revisedSpecialBasis * $specialRate) : 0;
        $revisedTaxCents        = $totalRevisedTaxCents - $revisedSpecialTaxCents;

        if ($revisedTaxCents < 0 || $revisedSpecialTaxCents < 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        $goodwillCents = $merchandiseCents - $revisedMerchandiseCents;

        if ($goodwillCents <= 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        // ── The identity, asserted in integers ──────────────────────────────
        $reconciled = $revisedBasisCents
            + $revisedUntaxedCents
            + $revisedTaxCents
            + $revisedSpecialTaxCents
            + $protectedCents
            - $discountCents;

        if ($reconciled !== $acceptedCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        $allocations = self::allocate($basis, $goodwillCents, $ordinaryRate, $revisedTaxCents);

        if ($allocations === null) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::AllocationImbalance);
        }

        return GoodwillCalculation::resolved(
            originalBasisCents:              $originalBasisCents,
            originalUntaxedMerchandiseCents: $originalUntaxedCents,
            originalTaxCents:        $originalTaxCents,
            originalSpecialTaxCents: $originalSpecialTaxCents,
            originalGrandTotalCents: $originalGrandTotalCents,
            revisedBasisCents:               $revisedBasisCents,
            revisedUntaxedMerchandiseCents:  $revisedUntaxedCents,
            revisedTaxCents:         $revisedTaxCents,
            revisedSpecialTaxCents:  $revisedSpecialTaxCents,
            revisedGrandTotalCents:  $acceptedCents,
            goodwillCents:           $goodwillCents,
            acceptedCents:           $acceptedCents,
            protectedCents:          $protectedCents,
            discountCents:           $discountCents,
            lineAllocations:         $allocations,
            basisSnapshot:           [
                'source'                => $basis->source?->value,
                'ordinary_basis_cents'  => $originalBasisCents,
                'ordinary_tax_cents'    => $originalTaxCents,
                'special_basis_cents'   => $basis->specialBasisCents,
                'special_tax_cents'     => $originalSpecialTaxCents,
                'untaxed_merchandise_cents' => $originalUntaxedCents,
                'protected_cents'       => $protectedCents,
                'discount_cents'        => $discountCents,
            ],
        );
    }

    /**
     * Spread the waived amount across eligible taxable lines.
     *
     * Proportional by the line's own basis, with leftover cents distributed by
     * largest fractional remainder and ties broken by ascending id — so the
     * same order always produces the same allocation, which is what makes an
     * exact reversal possible. Returns null if the shares fail to sum to the
     * waived total, which the caller treats as a hard failure rather than
     * absorbing the difference somewhere.
     *
     * Reduces `sub_total`, NOT `price`: ProductSalesPerformanceEngine reads
     * `order_products.sub_total` as its revenue source, and Goodwill is an
     * order-level concession rather than a repricing of the item.
     *
     * An order with no lines (a resolved extension child) allocates nothing —
     * there is no line to attribute the reduction to, and the order-level
     * totals carry it instead.
     *
     * @return array<int, array<string,int>>|null
     */
    private static function allocate(
        HistoricalTaxBasis $basis,
        int $goodwillCents,
        float $ordinaryRate,
        int $revisedTaxCents,
    ): ?array {
        // ALL reducible merchandise lines share the reduction — taxable and
        // untaxed alike. Untaxed merchandise is still merchandise (FD-002
        // Amendment 3); only protected fee-type lines are excluded.
        $eligible = array_values(array_filter(
            $basis->lines,
            fn (array $l) => ($l['reducible'] ?? true),
        ));

        if ($eligible === []) {
            return [];
        }

        $totalBasis = array_sum(array_column($eligible, 'basis_cents'));

        if ($totalBasis <= 0) {
            return null;
        }

        $shares     = [];
        $remainders = [];
        $assigned   = 0;

        foreach ($eligible as $i => $line) {
            $exact             = $goodwillCents * $line['basis_cents'] / $totalBasis;
            $shares[$i]        = (int) floor($exact);
            $remainders[$i]    = $exact - $shares[$i];
            $assigned         += $shares[$i];
        }

        // Largest remainder first; ties fall to the lower line id for determinism.
        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders, $eligible) {
            if ($remainders[$a] === $remainders[$b]) {
                return $eligible[$a]['id'] <=> $eligible[$b]['id'];
            }

            return $remainders[$b] <=> $remainders[$a];
        });

        $leftover = $goodwillCents - $assigned;
        foreach ($order as $i) {
            if ($leftover <= 0) {
                break;
            }
            $shares[$i]++;
            $leftover--;
        }

        if (array_sum($shares) !== $goodwillCents) {
            return null;
        }

        // Recompute tax only on lines that actually carried ordinary tax; an
        // untaxed merchandise line stays untaxed however much it is reduced.
        // The residual cent lands on the last TAXED line, mirroring
        // SalesTaxReportEngine's remainder-to-last-row convention rather than
        // inventing a second one.
        $allocations    = [];
        $taxAssigned    = 0;
        $taxedIndexes   = array_keys(array_filter($eligible, fn (array $l) => $l['taxable']));
        $lastTaxedIndex = $taxedIndexes === [] ? null : end($taxedIndexes);

        foreach ($eligible as $i => $line) {
            $basisAfter = $line['basis_cents'] - $shares[$i];

            if ($basisAfter < 0) {
                return null;
            }

            if (! $line['taxable']) {
                $taxAfter = 0;
            } elseif ($i === $lastTaxedIndex) {
                $taxAfter = $revisedTaxCents - $taxAssigned;
            } else {
                $taxAfter = (int) round($basisAfter * $ordinaryRate);
                $taxAssigned += $taxAfter;
            }

            if ($taxAfter < 0) {
                return null;
            }

            $allocations[] = [
                'line_id'      => $line['id'],
                'basis_before' => $line['basis_cents'],
                'basis_after'  => $basisAfter,
                'tax_before'   => $line['tax_cents'],
                'tax_after'    => $taxAfter,
                'reduced_by'   => $shares[$i],
            ];
        }

        if (array_sum(array_column($allocations, 'tax_after')) !== $revisedTaxCents) {
            return null;
        }

        return $allocations;
    }
}
