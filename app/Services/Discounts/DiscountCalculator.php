<?php

namespace App\Services\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountType;
use App\Services\TaxCalculationService;
use InvalidArgumentException;

/**
 * The single canonical PRE-TAX product-discount calculation boundary — pure,
 * no persistence. Controllers/services never compute a discount or tax
 * themselves; they call this and read the structured {@see DiscountResult}.
 *
 * Canonical pricing sequence (tax ALWAYS recalculated AFTER the discount):
 *
 *   original_product_value − discount = discounted_taxable_value
 *   + TaxCalculationService::addTaxToExclusiveAmount(discounted, rate)
 *   = final amount due
 *
 * Tax math is delegated to the canonical TaxCalculationService (the same
 * primitive orders/fuel/damage/extensions already use) — never re-derived here.
 * The discount can never push the product value below $0.00, and can never
 * discount tax.
 */
class DiscountCalculator
{
    /**
     * @param float $eligibleProductValue  the pre-tax base for this target
     * @param float $sourceAmount          for fixed_amount: the requested $; the
     *                                     caller (application service) must have
     *                                     already capped Store Credit by available
     *                                     balance before calling.
     * @param float $percentage            for percentage: e.g. 10.0 for 10%
     * @param float $taxRate               decimal rate (0.0975); pass 0.0 when the
     *                                     surface's own exemption signal says exempt
     */
    public function calculate(
        float $eligibleProductValue,
        DiscountType $type,
        DiscountCalculationType $calculationType,
        ?float $sourceAmount,
        ?float $percentage,
        float $taxRate,
    ): DiscountResult {
        if ($eligibleProductValue < 0) {
            throw new InvalidArgumentException('Eligible product value cannot be negative.');
        }
        $this->assertPairing($type, $calculationType);

        $rawDiscount = match ($calculationType) {
            DiscountCalculationType::FixedAmount => (float) ($sourceAmount ?? 0),
            DiscountCalculationType::Percentage  => round($eligibleProductValue * ((float) ($percentage ?? 0)) / 100, 2),
        };

        if ($rawDiscount < 0) {
            throw new InvalidArgumentException('Discount amount cannot be negative.');
        }

        // Never reduce below $0.00 — the discount is capped at the eligible base.
        $discount = round(min($rawDiscount, $eligibleProductValue), 2);
        $discountedBase = round($eligibleProductValue - $discount, 2);

        // Tax recalculated AFTER the discount, on the discounted base, via the
        // canonical primitive. rate=0 → zero tax (surface exemption honored).
        $before = TaxCalculationService::addTaxToExclusiveAmount($eligibleProductValue, $taxRate);
        $after = TaxCalculationService::addTaxToExclusiveAmount($discountedBase, $taxRate);

        return new DiscountResult(
            discountType: $type,
            calculationType: $calculationType,
            originalProductValue: round($eligibleProductValue, 2),
            discountAmount: $discount,
            discountedProductValue: $discountedBase,
            taxBefore: $before->taxAmount,
            taxAfter: $after->taxAmount,
            totalBefore: $before->totalAmount,
            finalAmountDue: $after->totalAmount,
        );
    }

    private function assertPairing(DiscountType $type, DiscountCalculationType $calc): void
    {
        $expected = $type->defaultCalculationType();
        if ($calc !== $expected) {
            throw new InvalidArgumentException(
                "Discount type {$type->value} requires calculation type {$expected->value}, got {$calc->value}."
            );
        }
    }
}
