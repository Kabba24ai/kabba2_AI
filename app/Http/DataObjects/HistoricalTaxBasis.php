<?php

namespace App\Http\DataObjects;

use App\Enums\Orders\HistoricalTaxBasisFailure;
use App\Enums\Orders\HistoricalTaxBasisSource;

/**
 * The reconstructed historical tax basis of one order, in INTEGER CENTS.
 *
 * Every monetary field is an int of cents, never a float and never dollars.
 * That is the point of this object: the rate this thing exists to produce is
 * a division, and a division performed against the wrong denominator is
 * exactly the defect that motivated it (`orders.tax_amount / orders.subtotal`
 * counts tax-free lines in the denominator, understating the rate on any
 * mixed order — live in PaymentAllocationService today). Callers read the
 * named basis they mean and never assemble one themselves.
 *
 * Ordinary sales tax and special tax are kept strictly separate — separate
 * bases, separate rates, never summed into one blended rate (FD-002
 * Amendment 1 §2). Added fees are flat per-unit charges, are not basis-
 * derived, and are carried here only so callers can exclude them.
 *
 * A failed resolution carries a named {@see HistoricalTaxBasisFailure} and
 * zeroed amounts. There is deliberately no "best effort" mode.
 */
final class HistoricalTaxBasis
{
    /**
     * @param  array<int, array{id:int, basis_cents:int, tax_cents:int, taxable:bool}>  $lines
     */
    private function __construct(
        /** Basis that carried ORDINARY sales tax, in cents. The only permitted denominator for the ordinary rate. */
        public readonly int $ordinaryBasisCents,

        /** Ordinary sales tax actually recorded, in cents. */
        public readonly int $ordinaryTaxCents,

        /** Basis that carried SPECIAL tax, in cents. May differ from the ordinary basis. */
        public readonly int $specialBasisCents,

        /** Special tax actually recorded, in cents. */
        public readonly int $specialTaxCents,

        /**
         * Merchandise that carried no ordinary sales tax, in cents.
         *
         * REDUCIBLE. A tax-exempt order, customer, or product still sold
         * merchandise; it simply was not taxed. Goodwill may reduce this
         * exactly as it reduces taxable merchandise — the absence of tax says
         * nothing about whether the line is part of the sale.
         */
        public readonly int $untaxedMerchandiseBasisCents,

        /** Flat added fees, in cents. PROTECTED — not merchandise, never reduced. */
        public readonly int $addedFeesCents,

        /**
         * Line amounts proving, from frozen data, that they are a fee-type
         * component rather than merchandise. PROTECTED.
         *
         * Currently always zero: `product_type` is only 'Rental' or 'Retail'
         * in this schema, both merchandise, so no order line can prove
         * fee-type. The bucket exists so the rule is already stated if a
         * fee-type product is ever introduced, rather than being decided
         * under pressure later.
         */
        public readonly int $protectedLineCents,

        /** Order-level discount, in cents. Applied post-tax by existing behavior. */
        public readonly int $discountCents,

        /** Per-line reconstruction, for deterministic allocation and exact reversal. */
        public readonly array $lines,

        /** Null when resolution succeeded. */
        public readonly ?HistoricalTaxBasisFailure $failure,

        /** Where this basis was reconstructed from. Null on failure. Logged so every refund split is traceable to its evidence. */
        public readonly ?HistoricalTaxBasisSource $source = null,
    ) {}

    /**
     * @param  array<int, array{id:int, basis_cents:int, tax_cents:int, taxable:bool}>  $lines
     */
    public static function resolved(
        int $ordinaryBasisCents,
        int $ordinaryTaxCents,
        int $specialBasisCents,
        int $specialTaxCents,
        int $untaxedMerchandiseBasisCents,
        int $addedFeesCents,
        int $discountCents,
        array $lines,
        HistoricalTaxBasisSource $source = HistoricalTaxBasisSource::OrderProductLines,
        int $protectedLineCents = 0,
    ): self {
        return new self(
            $ordinaryBasisCents, $ordinaryTaxCents,
            $specialBasisCents, $specialTaxCents,
            $untaxedMerchandiseBasisCents, $addedFeesCents, $protectedLineCents, $discountCents,
            $lines, null, $source,
        );
    }

    public static function failed(HistoricalTaxBasisFailure $failure): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, [], $failure, null);
    }

    public function succeeded(): bool
    {
        return $this->failure === null;
    }

    /**
     * The ordinary sales-tax rate, derived from the ordinary basis alone.
     *
     * A float ONLY because TaxCalculationService's current contract takes a
     * float rate; that service is the single authorized calculation boundary
     * (FD-002), and modernizing its contract is recorded technical debt. Do
     * not persist this value, compare it, or do further arithmetic with it —
     * the cents fields above are authoritative.
     */
    public function ordinaryRate(): float
    {
        return $this->ordinaryBasisCents > 0
            ? $this->ordinaryTaxCents / $this->ordinaryBasisCents
            : 0.0;
    }

    /**
     * Taxable + untaxed merchandise: everything Goodwill is permitted to
     * reduce. Protected components are deliberately excluded.
     */
    public function adjustableMerchandiseBasisCents(): int
    {
        return $this->ordinaryBasisCents + $this->untaxedMerchandiseBasisCents;
    }

    /** Everything Goodwill must NOT reduce: flat fees plus any proven fee-type line. */
    public function protectedCents(): int
    {
        return $this->addedFeesCents + $this->protectedLineCents;
    }

    /** The special-tax rate. Same float caveat as {@see ordinaryRate()}. Never summed with it into a blended rate. */
    public function specialRate(): float
    {
        return $this->specialBasisCents > 0
            ? $this->specialTaxCents / $this->specialBasisCents
            : 0.0;
    }
}
