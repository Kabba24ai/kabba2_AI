<?php

namespace App\Http\DataObjects;

/**
 * WHY a Goodwill Adjustment could not be calculated.
 *
 * Distinct from HistoricalTaxBasisFailure: that says the order's ORIGINAL tax
 * basis is unreconstructable; these say the basis was fine but the requested
 * adjustment itself is not valid. Both are named rather than returned as a
 * null or a zero, so no caller can mistake "cannot" for "nothing to do".
 */
enum GoodwillCalculationFailure: string
{
    /** The order's historical taxable basis could not be reconstructed. */
    case BasisUnreconstructable = 'basis_unreconstructable';

    /** Cumulative settled payments are zero — a $0.00 concession is a write-off, undecided per Truth Table §5.9. */
    case NoPaymentReceived = 'no_payment_received';

    /** Payments already meet or exceed the order total; there is nothing to waive. */
    case NothingToWaive = 'nothing_to_waive';

    /** The accepted amount cannot even cover the order's non-reducible components. */
    case PaymentBelowNonReducibleFloor = 'payment_below_non_reducible_floor';

    /** The reconciliation identity failed in integer cents — a bug, not a user error. Nothing is written. */
    case ReconciliationFailed = 'reconciliation_failed';

    /** Line-level allocation did not sum exactly to the waived amount. Nothing is written. */
    case AllocationImbalance = 'allocation_imbalance';

    public function message(): string
    {
        return match ($this) {
            self::BasisUnreconstructable => 'This order\'s original taxable basis cannot be reconstructed, so Goodwill cannot be calculated.',
            self::NoPaymentReceived => 'Goodwill requires a payment to have been received. Closing an order with nothing collected is a write-off, which is a separate decision.',
            self::NothingToWaive => 'Payments already cover this order\'s total, so there is no remaining balance to waive.',
            self::PaymentBelowNonReducibleFloor => 'The amount received does not cover this order\'s non-taxable charges and fees, which Goodwill cannot reduce.',
            self::ReconciliationFailed => 'The revised totals did not reconcile exactly. No changes were made.',
            self::AllocationImbalance => 'The Goodwill amount could not be allocated exactly across product lines. No changes were made.',
        };
    }
}
