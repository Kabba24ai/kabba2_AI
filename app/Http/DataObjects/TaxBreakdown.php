<?php

namespace App\Http\DataObjects;

/**
 * Plain PHP value object returned by TaxCalculationService.
 *
 * Exists specifically so a tax calculation result can never be mistaken for
 * a bare float — the Billing Summary bug (a $150.00 payment displaying as
 * $164.63) happened because a tax-inclusive amount was treated as if it
 * needed tax added on top. Callers must read the named field they mean
 * (baseAmount vs. taxAmount vs. totalAmount) rather than doing their own
 * arithmetic on an ambiguous number.
 */
final class TaxBreakdown
{
    public function __construct(
        /** The pre-tax portion, in dollars. */
        public readonly float $baseAmount,

        /** The tax portion, in dollars. */
        public readonly float $taxAmount,

        /** baseAmount + taxAmount, in dollars. */
        public readonly float $totalAmount,
    ) {}
}
