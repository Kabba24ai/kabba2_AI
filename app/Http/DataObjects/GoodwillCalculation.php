<?php

namespace App\Http\DataObjects;

use App\Enums\Orders\HistoricalTaxBasisFailure;

/**
 * The computed shape of one Goodwill Adjustment, in INTEGER CENTS.
 *
 * Produced by GoodwillAdjustmentService's single calculation path and used
 * unchanged by both preview and apply, so the numbers an approving manager
 * sees cannot differ from the numbers committed.
 *
 * A failed calculation carries a reason and zeroed amounts. There is no
 * partially-valid result: FD-002 requires the reconciliation identity to hold
 * exactly, in integers, or nothing is written.
 */
final class GoodwillCalculation
{
    private function __construct(
        /** Ordinary TAXABLE merchandise basis before the adjustment. */
        public readonly int $originalBasisCents,

        /** Reducible UNTAXED merchandise basis before. Tax-exempt merchandise is still merchandise. */
        public readonly int $originalUntaxedMerchandiseCents,
        /** Ordinary sales tax before. */
        public readonly int $originalTaxCents,
        /** Special tax before. */
        public readonly int $originalSpecialTaxCents,
        /** Order grand total before. */
        public readonly int $originalGrandTotalCents,

        /** Ordinary taxable merchandise basis after. */
        public readonly int $revisedBasisCents,

        /** Untaxed merchandise basis after. */
        public readonly int $revisedUntaxedMerchandiseCents,
        /** Ordinary sales tax recomputed on the reduced basis. */
        public readonly int $revisedTaxCents,
        /** Special tax recomputed on the reduced basis at its OWN rate. */
        public readonly int $revisedSpecialTaxCents,
        /** Revised grand total — equals the accepted amount exactly. */
        public readonly int $revisedGrandTotalCents,

        /** The waived amount: originalBasis - revisedBasis. */
        public readonly int $goodwillCents,

        /** Cumulative settled payments this adjustment closes the order at. */
        public readonly int $acceptedCents,

        /** PROTECTED components Goodwill may never reduce (flat fees, proven fee-type lines). */
        public readonly int $protectedCents,

        /** The fee-type LINE portion of $protectedCents. Always 0 today; the writer refuses if it is not. */
        public readonly int $protectedLineCents,
        public readonly int $discountCents,

        /** Per-line reduction: [line_id => ['basis_before','basis_after','tax_before','tax_after']]. */
        public readonly array $lineAllocations,

        /** Basis/rate/source provenance, persisted with the adjustment. */
        public readonly array $basisSnapshot,

        public readonly ?GoodwillCalculationFailure $failure,
        public readonly ?HistoricalTaxBasisFailure $basisFailure,
    ) {}

    public static function resolved(
        int $originalBasisCents,
        int $originalUntaxedMerchandiseCents,
        int $originalTaxCents,
        int $originalSpecialTaxCents,
        int $originalGrandTotalCents,
        int $revisedBasisCents,
        int $revisedUntaxedMerchandiseCents,
        int $revisedTaxCents,
        int $revisedSpecialTaxCents,
        int $revisedGrandTotalCents,
        int $goodwillCents,
        int $acceptedCents,
        int $protectedCents,
        int $protectedLineCents,
        int $discountCents,
        array $lineAllocations,
        array $basisSnapshot,
    ): self {
        return new self(
            $originalBasisCents, $originalUntaxedMerchandiseCents, $originalTaxCents, $originalSpecialTaxCents, $originalGrandTotalCents,
            $revisedBasisCents, $revisedUntaxedMerchandiseCents, $revisedTaxCents, $revisedSpecialTaxCents, $revisedGrandTotalCents,
            $goodwillCents, $acceptedCents,
            $protectedCents, $protectedLineCents, $discountCents,
            $lineAllocations, $basisSnapshot, null, null,
        );
    }

    public static function failed(
        GoodwillCalculationFailure $failure,
        ?HistoricalTaxBasisFailure $basisFailure = null,
    ): self {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, [], [], $failure, $basisFailure);
    }

    public function succeeded(): bool
    {
        return $this->failure === null;
    }

    /** Merchandise Goodwill was permitted to reduce, before the adjustment. */
    public function originalMerchandiseCents(): int
    {
        return $this->originalBasisCents + $this->originalUntaxedMerchandiseCents;
    }

    /** Merchandise remaining after the adjustment. */
    public function revisedMerchandiseCents(): int
    {
        return $this->revisedBasisCents + $this->revisedUntaxedMerchandiseCents;
    }

    /** Dollars, for display only. Never feed these back into a calculation. */
    public function goodwillDollars(): float
    {
        return round($this->goodwillCents / 100, 2);
    }
}
