<?php

namespace Tests\Unit\Services;

use App\Services\ChargeTaxCalculator;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Sales Tax Architecture Audit / Correction — canonical implementation of
 * the three Sales Tax Treatment choices already exposed in the CRM "New
 * Charge" modal. Genuinely executable, DB-free (pure arithmetic).
 */
class ChargeTaxCalculatorTest extends TestCase
{
    public function test_add_sales_tax_adds_tax_on_top_of_the_entered_amount(): void
    {
        $result = ChargeTaxCalculator::calculate(100.0, 'add', 0.0975);

        $this->assertSame('add', $result['treatment']);
        $this->assertSame(100.0, $result['base_amount']);
        $this->assertSame(9.75, $result['tax_amount']);
        $this->assertSame(109.75, $result['total_amount']);
    }

    public function test_tax_free_produces_exactly_zero_tax(): void
    {
        $result = ChargeTaxCalculator::calculate(250.0, 'free', 0.0975);

        $this->assertSame(250.0, $result['base_amount']);
        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(250.0, $result['total_amount']);
    }

    public function test_tax_free_ignores_the_rate_entirely(): void
    {
        // Even an absurd rate must never leak into a Tax Free charge.
        $result = ChargeTaxCalculator::calculate(100.0, 'free', 0.50);

        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(100.0, $result['total_amount']);
    }

    public function test_reverse_sales_tax_splits_the_entered_total_proportionally_without_adding_on_top(): void
    {
        // Entered amount IS the tax-inclusive total — must not become base+tax on top.
        $result = ChargeTaxCalculator::calculate(109.75, 'reverse', 0.0975);

        $this->assertSame(109.75, $result['total_amount']);
        $this->assertEqualsWithDelta(100.0, $result['base_amount'], 0.01);
        $this->assertEqualsWithDelta(9.75, $result['tax_amount'], 0.01);
    }

    public function test_reverse_sales_tax_with_zero_rate_treats_the_whole_amount_as_base(): void
    {
        $result = ChargeTaxCalculator::calculate(100.0, 'reverse', 0.0);

        $this->assertSame(100.0, $result['base_amount']);
        $this->assertSame(0.0, $result['tax_amount']);
    }

    public function test_zero_entered_amount_produces_zero_everything(): void
    {
        foreach (['add', 'free', 'reverse'] as $treatment) {
            $result = ChargeTaxCalculator::calculate(0.0, $treatment, 0.0975);
            $this->assertSame(0.0, $result['base_amount'], "treatment={$treatment}");
            $this->assertSame(0.0, $result['tax_amount'], "treatment={$treatment}");
            $this->assertSame(0.0, $result['total_amount'], "treatment={$treatment}");
        }
    }

    public function test_decimal_cent_edge_case_rounds_correctly(): void
    {
        $result = ChargeTaxCalculator::calculate(33.33, 'add', 0.0975);

        // 33.33 * 0.0975 = 3.249675 -> rounds to 3.25
        $this->assertSame(3.25, $result['tax_amount']);
        $this->assertSame(36.58, $result['total_amount']);
    }

    public function test_invalid_treatment_is_rejected_not_silently_defaulted(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChargeTaxCalculator::calculate(100.0, 'bogus', 0.0975);
    }

    public function test_negative_rate_is_clamped_to_zero(): void
    {
        $result = ChargeTaxCalculator::calculate(100.0, 'add', -0.05);

        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(0.0, $result['tax_rate']);
    }

    public function test_is_valid_treatment(): void
    {
        $this->assertTrue(ChargeTaxCalculator::isValidTreatment('add'));
        $this->assertTrue(ChargeTaxCalculator::isValidTreatment('free'));
        $this->assertTrue(ChargeTaxCalculator::isValidTreatment('reverse'));
        $this->assertFalse(ChargeTaxCalculator::isValidTreatment('bogus'));
        $this->assertFalse(ChargeTaxCalculator::isValidTreatment(null));
    }
}
