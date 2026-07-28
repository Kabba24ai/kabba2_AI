<?php

namespace App\Services\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountType;

/**
 * Immutable result of a pure discount calculation. Carries the full pricing
 * sequence so callers (and the persisted product_discounts row) never redo the
 * math:
 *
 *   original_product_value − discount = discounted_taxable_value
 *   + recalculated sales tax = final amount due
 */
final class DiscountResult
{
    public function __construct(
        public readonly DiscountType $discountType,
        public readonly DiscountCalculationType $calculationType,
        public readonly float $originalProductValue,   // eligible pre-tax base
        public readonly float $discountAmount,          // actually applied (capped)
        public readonly float $discountedProductValue,  // base − discount (>= 0)
        public readonly float $taxBefore,
        public readonly float $taxAfter,
        public readonly float $totalBefore,             // base + taxBefore
        public readonly float $finalAmountDue,          // discountedBase + taxAfter
    ) {
    }

    public function toArray(): array
    {
        return [
            'discount_type' => $this->discountType->value,
            'calculation_type' => $this->calculationType->value,
            'original_product_value' => $this->originalProductValue,
            'calculated_discount_amount' => $this->discountAmount,
            'discounted_product_value' => $this->discountedProductValue,
            'taxable_value_before' => $this->originalProductValue,
            'taxable_value_after' => $this->discountedProductValue,
            'tax_before' => $this->taxBefore,
            'tax_after' => $this->taxAfter,
            'total_before' => $this->totalBefore,
            'final_amount_due' => $this->finalAmountDue,
        ];
    }
}
