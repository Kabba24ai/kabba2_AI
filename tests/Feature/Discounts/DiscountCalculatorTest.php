<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountType;
use App\Services\Discounts\DiscountCalculator;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The pure discount-calculation boundary. Tax is ALWAYS recalculated after the
 * discount on the discounted base, via the canonical TaxCalculationService.
 */
class DiscountCalculatorTest extends TestCase
{
    private const RATE = 0.0975;

    private function calc(): DiscountCalculator
    {
        return new DiscountCalculator();
    }

    public function test_partial_store_credit_example(): void
    {
        // Spec example: $1,000 base, −$500 SC, tax 9.75% on $500 → $48.75 → $548.75.
        $r = $this->calc()->calculate(1000.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 500.00, null, self::RATE);

        $this->assertEquals(1000.00, $r->originalProductValue);
        $this->assertEquals(500.00, $r->discountAmount);
        $this->assertEquals(500.00, $r->discountedProductValue);
        $this->assertEquals(48.75, $r->taxAfter);
        $this->assertEquals(548.75, $r->finalAmountDue);
    }

    public function test_full_store_credit_example(): void
    {
        // $500 base, −$500 SC → $0 taxable, $0 tax, $0 due.
        $r = $this->calc()->calculate(500.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 500.00, null, self::RATE);

        $this->assertEquals(0.00, $r->discountedProductValue);
        $this->assertEquals(0.00, $r->taxAfter);
        $this->assertEquals(0.00, $r->finalAmountDue);
    }

    public function test_discount_cannot_reduce_below_zero(): void
    {
        // Requesting more than the base is capped at the base (never negative).
        $r = $this->calc()->calculate(300.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 999.00, null, self::RATE);

        $this->assertEquals(300.00, $r->discountAmount, 'capped at eligible base');
        $this->assertEquals(0.00, $r->discountedProductValue);
        $this->assertEquals(0.00, $r->finalAmountDue);
    }

    public function test_tax_is_calculated_after_discount_not_before(): void
    {
        $r = $this->calc()->calculate(1000.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 400.00, null, self::RATE);
        // tax must be on the discounted $600, not the original $1000.
        $this->assertEquals(round(600 * self::RATE, 2), $r->taxAfter);
        $this->assertEquals(round(1000 * self::RATE, 2), $r->taxBefore);
    }

    public function test_original_value_is_preserved(): void
    {
        $r = $this->calc()->calculate(112.00, DiscountType::Goodwill, DiscountCalculationType::FixedAmount, 12.00, null, self::RATE);
        $this->assertEquals(112.00, $r->originalProductValue, 'original recoverable');
        $this->assertEquals(100.00, $r->discountedProductValue);
    }

    public function test_percentage_discount_general_type(): void
    {
        // Future general 10% of $1,000 → $100 off → tax on $900.
        $r = $this->calc()->calculate(1000.00, DiscountType::General, DiscountCalculationType::Percentage, null, 10.0, self::RATE);
        $this->assertEquals(100.00, $r->discountAmount);
        $this->assertEquals(900.00, $r->discountedProductValue);
        $this->assertEquals(round(900 * self::RATE, 2), $r->taxAfter);
    }

    public function test_exempt_surface_zero_rate_produces_no_tax(): void
    {
        $r = $this->calc()->calculate(500.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 200.00, null, 0.0);
        $this->assertEquals(0.00, $r->taxAfter);
        $this->assertEquals(300.00, $r->finalAmountDue);
    }

    public function test_rounding_is_deterministic(): void
    {
        $r = $this->calc()->calculate(333.33, DiscountType::General, DiscountCalculationType::Percentage, null, 7.5, self::RATE);
        // 7.5% of 333.33 = 24.99975 → round → 25.00
        $this->assertEquals(25.00, $r->discountAmount);
        $this->assertEquals(308.33, $r->discountedProductValue);
    }

    public function test_wrong_calculation_type_for_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // store_credit must be fixed_amount, not percentage.
        $this->calc()->calculate(100.00, DiscountType::StoreCredit, DiscountCalculationType::Percentage, null, 10.0, self::RATE);
    }

    public function test_negative_base_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calc()->calculate(-1.00, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, 1.00, null, self::RATE);
    }
}
