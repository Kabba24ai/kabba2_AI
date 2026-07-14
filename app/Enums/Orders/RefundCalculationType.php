<?php

namespace App\Enums\Orders;

/**
 * HOW a refund's dollar amount was calculated — kept strictly separate
 * from {@see ProcessedReason}, which records WHY the refund was issued.
 * Stored on the refund's order_payments row (refund_calculation_type) so
 * the behavior stays auditable rather than inferred later from the amount.
 */
enum RefundCalculationType: string
{
    /** The entered amount, proportionally split between purchase and sales tax (unchanged, pre-existing behavior). */
    case Standard = 'standard';

    /** Eligible refundable balance minus the configured Credit Card Processing Fee, retained by the company. */
    case CardProcessingFeeRetained = 'card_processing_fee_retained';

    /** The entire refund reduces sales tax only — no purchase/rental revenue impact. */
    case SalesTaxOnly = 'sales_tax_only';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::CardProcessingFeeRetained => 'Card Processing Fee Retained',
            self::SalesTaxOnly => 'Sales Tax Only',
        };
    }
}
