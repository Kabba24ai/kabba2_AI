<?php

namespace App\Enums\Orders;

/**
 * WHY a historical tax basis could not be reconstructed for an order.
 *
 * Every unsupported or corrupt state gets its own case. This exists so no
 * caller ever has to interpret a null, a zero, or a "close enough" rate:
 * per FD-002 Amendment 1 §4, the feature must never infer a plausible-
 * looking rate from an incorrect denominator, so the resolver reports a
 * named reason instead of guessing.
 *
 * {@see \App\Services\Orders\HistoricalTaxBasisResolver}
 */
enum HistoricalTaxBasisFailure: string
{
    /** The order has no order_products rows to reconstruct anything from. */
    case NoLineData = 'no_line_data';

    /** Stored tax is nonzero but no line carried any taxable basis — the rate's denominator would be zero. */
    case ZeroBasisWithTax = 'zero_basis_with_tax';

    /** Taxable lines disagree on their effective rate beyond what rounding explains. */
    case MixedTaxRates = 'mixed_tax_rates';

    /** Summed line tax does not reconcile with orders.tax_amount. */
    case UnreconciledTax = 'unreconciled_tax';

    /** Summed line subtotal does not reconcile with orders.subtotal. */
    case UnreconciledSubtotal = 'unreconciled_subtotal';

    /** Stored totals do not reconcile with each other (subtotal + taxes + fees - discount != grand_total). */
    case UnreconciledGrandTotal = 'unreconciled_grand_total';

    /** A line carries stored tax that its own recorded basis cannot explain — a manual override with no derivation. */
    case UnexplainedTaxOverride = 'unexplained_tax_override';

    /** product_data is absent, unparseable, or disagrees with the stored columns. */
    case FrozenDataUnavailable = 'frozen_data_unavailable';

    /** An existing pre-tax adjustment already changed the basis, making the denominator ambiguous. */
    case AmbiguousExistingAdjustment = 'ambiguous_existing_adjustment';

    /**
     * Operator-facing explanation. Deliberately says what is wrong with the
     * DATA, not what the user did — every one of these is a property of the
     * order as stored, never of the request.
     */
    public function message(): string
    {
        return match ($this) {
            self::NoLineData => 'This order has no product lines, so its original taxable basis cannot be reconstructed.',
            self::ZeroBasisWithTax => 'This order records sales tax but no taxable product basis, so the original tax rate cannot be derived.',
            self::MixedTaxRates => 'This order\'s lines were taxed at more than one rate. A single historical rate cannot be derived.',
            self::UnreconciledTax => 'The sum of line-level tax does not match the order\'s recorded sales tax.',
            self::UnreconciledSubtotal => 'The sum of line-level subtotals does not match the order\'s recorded subtotal.',
            self::UnreconciledGrandTotal => 'This order\'s stored totals do not reconcile with one another.',
            self::UnexplainedTaxOverride => 'A product line carries sales tax that its own recorded amount cannot explain.',
            self::FrozenDataUnavailable => 'The frozen product data needed to separate special tax and added fees is missing or unreadable.',
            self::AmbiguousExistingAdjustment => 'An existing adjustment already altered this order\'s basis, so the original basis is ambiguous.',
        };
    }
}
