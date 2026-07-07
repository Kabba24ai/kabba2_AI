<?php

namespace Tests\Unit\Services;

use App\Services\TaxCalculationService;
use PHPUnit\Framework\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    /**
     * Reproduces the exact numbers from the confirmed Billing Summary bug:
     * a $150.00 payment made up of $136.67 principal + $13.33 tax.
     * docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md §4.1
     */
    public function test_extract_tax_from_inclusive_amount_reproduces_original_bug_report_numbers(): void
    {
        $rate = 13.33 / 136.67;

        $result = TaxCalculationService::extractTaxFromInclusiveAmount(150.00, $rate);

        $this->assertSame(136.67, $result->baseAmount);
        $this->assertSame(13.33, $result->taxAmount);
        $this->assertSame(150.00, $result->totalAmount);
    }

    public function test_extract_tax_from_inclusive_amount_with_zero_rate_treats_full_amount_as_base(): void
    {
        $result = TaxCalculationService::extractTaxFromInclusiveAmount(150.00, 0.0);

        $this->assertSame(150.00, $result->baseAmount);
        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(150.00, $result->totalAmount);
    }

    public function test_extract_tax_from_inclusive_amount_with_negative_rate_treats_full_amount_as_base(): void
    {
        // Defensive case: a negative rate should never occur in practice, but
        // must not produce a nonsensical negative tax amount.
        $result = TaxCalculationService::extractTaxFromInclusiveAmount(100.00, -0.05);

        $this->assertSame(100.00, $result->baseAmount);
        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(100.00, $result->totalAmount);
    }

    public function test_extract_tax_from_inclusive_amount_with_zero_amount(): void
    {
        $result = TaxCalculationService::extractTaxFromInclusiveAmount(0.0, 0.0887);

        $this->assertSame(0.0, $result->baseAmount);
        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(0.0, $result->totalAmount);
    }

    public function test_extract_tax_from_inclusive_amount_rounds_to_cents(): void
    {
        $result = TaxCalculationService::extractTaxFromInclusiveAmount(10.00, 0.0887);

        // base = 10 / 1.0887 = 9.185175... -> 9.19; tax = 10 - 9.19 = 0.81
        $this->assertSame(9.19, $result->baseAmount);
        $this->assertSame(0.81, $result->taxAmount);
        $this->assertSame(10.00, $result->totalAmount);
    }

    /**
     * Regression test for a discrepancy found during Phase 2.2's equivalence
     * validation (docs/financial-engine-consolidation/PHASE_2_2_COMPLETION_REPORT.md):
     * an earlier version of this method rounded baseAmount first and derived
     * taxAmount from the already-rounded base, which disagreed with the
     * production formula (round both from full-precision intermediate values)
     * by one cent for roughly 1 in 150,000 realistic amount/rate combinations,
     * concentrated at larger dollar amounts. This exact input is one such case.
     */
    public function test_extract_tax_from_inclusive_amount_matches_production_on_known_divergent_input(): void
    {
        $result = TaxCalculationService::extractTaxFromInclusiveAmount(31423.99, 0.04);

        $this->assertSame(30215.38, $result->baseAmount);
        $this->assertSame(1208.62, $result->taxAmount);
        $this->assertSame(31423.99, $result->totalAmount);
    }

    /**
     * Mirrors the correct, already-shipped formula in
     * resources/views/admin/order_management/orders/partials/_additional_charges.blade.php:32-35
     * for a pre-tax manual charge.
     */
    public function test_add_tax_to_exclusive_amount_matches_additional_charges_blade_formula(): void
    {
        $result = TaxCalculationService::addTaxToExclusiveAmount(100.00, 0.0887);

        $this->assertSame(100.00, $result->baseAmount);
        $this->assertSame(8.87, $result->taxAmount);
        $this->assertSame(108.87, $result->totalAmount);
    }

    public function test_add_tax_to_exclusive_amount_with_zero_rate_adds_no_tax(): void
    {
        $result = TaxCalculationService::addTaxToExclusiveAmount(100.00, 0.0);

        $this->assertSame(100.00, $result->baseAmount);
        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(100.00, $result->totalAmount);
    }

    public function test_add_tax_to_exclusive_amount_with_zero_amount(): void
    {
        $result = TaxCalculationService::addTaxToExclusiveAmount(0.0, 0.0887);

        $this->assertSame(0.0, $result->baseAmount);
        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(0.0, $result->totalAmount);
    }

    public function test_add_tax_to_exclusive_amount_rounds_to_cents(): void
    {
        $result = TaxCalculationService::addTaxToExclusiveAmount(33.33, 0.0887);

        // tax = 33.33 * 0.0887 = 2.956221 -> 2.96; total = 33.33 + 2.96 = 36.29
        $this->assertSame(2.96, $result->taxAmount);
        $this->assertSame(36.29, $result->totalAmount);
    }

    /**
     * The two formulas are intentionally not inverses of each other at the
     * cent-rounded level for every input — extracting tax from an inclusive
     * amount and then adding tax back to the resulting base is not guaranteed
     * to reproduce the original amount exactly, because each step rounds to
     * the nearest cent independently. This test documents that as expected
     * behavior (matching the existing, already-shipped Blade formulas, which
     * have the same property) rather than a defect to fix here.
     */
    public function test_extract_then_add_may_differ_from_original_by_a_rounding_cent(): void
    {
        $rate = 0.0887;
        $original = 10.00;

        $extracted = TaxCalculationService::extractTaxFromInclusiveAmount($original, $rate);
        $rebuilt = TaxCalculationService::addTaxToExclusiveAmount($extracted->baseAmount, $rate);

        $this->assertEqualsWithDelta($original, $rebuilt->totalAmount, 0.01);
    }

    /**
     * Phase 2.2: the SQL form must be textually equivalent (ignoring only
     * insignificant whitespace) to the CASE expression it replaces in
     * SalesReportEngineV2::queryAccountPayments() / queryDailyAccountPayments(),
     * so migrating to the shared builder is a pure de-duplication, not a
     * formula change. See docs/financial-engine-consolidation/PHASE_2_2_COMPLETION_REPORT.md.
     */
    public function test_extract_tax_from_inclusive_amount_sql_matches_original_case_expression(): void
    {
        $expectedBase = "CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0"
            .' THEN amount / (1 + CAST(NULLIF(COALESCE(sales_tax, \'0\'), \'\') AS DECIMAL(10,6)))'
            .' ELSE amount END';

        $expectedTax = "CASE WHEN CAST(NULLIF(COALESCE(sales_tax, '0'), '') AS DECIMAL(10,6)) > 0"
            .' THEN amount - amount / (1 + CAST(NULLIF(COALESCE(sales_tax, \'0\'), \'\') AS DECIMAL(10,6)))'
            .' ELSE 0 END';

        $sql = TaxCalculationService::extractTaxFromInclusiveAmountSql();

        $this->assertSame($expectedBase, $sql['base']);
        $this->assertSame($expectedTax, $sql['tax']);
    }

    public function test_extract_tax_from_inclusive_amount_sql_supports_custom_column_names(): void
    {
        $sql = TaxCalculationService::extractTaxFromInclusiveAmountSql('ca.amount', 'ca.sales_tax');

        $this->assertStringContainsString('ca.amount', $sql['base']);
        $this->assertStringContainsString('ca.sales_tax', $sql['base']);
        $this->assertStringContainsString('ca.amount', $sql['tax']);
        $this->assertStringContainsString('ca.sales_tax', $sql['tax']);
    }

    /**
     * Phase 2.3: reproduces the exact numbers from the confirmed Dashboard
     * revenue/tax bug (docs/financial-engine-consolidation/PHASE_2_0_SIGNOFF_READINESS.md
     * §2.2) — a $150.00 payment previously had its base computed via the
     * wrong (multiplication) formula. This asserts the correct (division)
     * result.
     */
    public function test_extract_base_from_inclusive_amount_raw_reproduces_correct_dashboard_base(): void
    {
        $amount = 150.00;
        $rate = 0.0887;

        $base = TaxCalculationService::extractBaseFromInclusiveAmountRaw($amount, $rate);

        // Correct (division): 150 / 1.0887 = 137.77900248...
        // Old buggy (multiplication): 150 - (150 * 0.0887) = 136.695
        $this->assertEqualsWithDelta(137.77900248, $base, 0.0001);
    }

    public function test_extract_base_from_inclusive_amount_raw_with_zero_rate_returns_full_amount(): void
    {
        $this->assertSame(150.00, TaxCalculationService::extractBaseFromInclusiveAmountRaw(150.00, 0.0));
    }

    public function test_extract_base_from_inclusive_amount_raw_is_unrounded(): void
    {
        // 10 / 1.0887 = 9.185175... — deliberately NOT rounded to 9.19,
        // unlike extractTaxFromInclusiveAmount()'s baseAmount. Callers that
        // need to sum many rows before rounding once rely on this.
        $base = TaxCalculationService::extractBaseFromInclusiveAmountRaw(10.00, 0.0887);

        $this->assertNotEquals(round($base, 2), $base);
    }

    /**
     * Aggregation-safety proof: summing many rows' raw base amounts and
     * rounding once must match summing many rows' raw base amounts computed
     * inline with the exact formula SalesReportEngineV2 uses in SQL — i.e.
     * this method must be safe to sum before rounding, which is the entire
     * reason it exists instead of reusing extractTaxFromInclusiveAmount().
     */
    public function test_extract_base_from_inclusive_amount_raw_sums_safely_across_many_rows(): void
    {
        $rows = [
            [150.00, 0.0887],
            [75.50, 0.0887],
            [220.33, 0.05],
            [10.00, 0.0],
            [999.99, 0.1275],
        ];

        $sumViaService = array_sum(array_map(
            fn ($row) => TaxCalculationService::extractBaseFromInclusiveAmountRaw($row[0], $row[1]),
            $rows
        ));

        $sumViaInlineFormula = array_sum(array_map(
            fn ($row) => $row[1] > 0 ? $row[0] / (1 + $row[1]) : $row[0],
            $rows
        ));

        $this->assertSame(round($sumViaInlineFormula, 2), round($sumViaService, 2));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Phase 2.3A: Calculation Pattern documentation tests.
    //
    // These tests exist to make the transaction-safe vs. analytics-only
    // distinction (docs/financial-engine-consolidation/
    // PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md) executable, not just
    // written prose — if a future change accidentally makes the two
    // families behave the same way (or diverge in a new way), one of
    // these should fail.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * TRANSACTION-SAFE methods (extractTaxFromInclusiveAmount,
     * addTaxToExclusiveAmount) always round to cents. This is what makes
     * their output safe to store directly as a single transaction's
     * persisted tax/base amount.
     */
    public function test_transaction_safe_methods_always_return_rounded_cent_values(): void
    {
        $extracted = TaxCalculationService::extractTaxFromInclusiveAmount(10.00, 0.0887);
        $added = TaxCalculationService::addTaxToExclusiveAmount(10.00, 0.0887);

        foreach ([$extracted->baseAmount, $extracted->taxAmount, $added->baseAmount, $added->taxAmount] as $value) {
            $this->assertSame(round($value, 2), $value, "Transaction-safe output must already be rounded: {$value}");
        }
    }

    /**
     * ANALYTICS-ONLY methods (extractBaseFromInclusiveAmountRaw) deliberately
     * do NOT round — that is what makes them safe to SUM across many rows
     * before a single final rounding, and unsafe to store directly as a
     * single transaction's value without further processing.
     */
    public function test_analytics_only_methods_deliberately_do_not_round(): void
    {
        $raw = TaxCalculationService::extractBaseFromInclusiveAmountRaw(10.00, 0.0887);

        $this->assertNotSame(round($raw, 2), $raw, 'Analytics-only output must remain unrounded until aggregation completes.');
    }

    /**
     * The two families can disagree by a cent when applied to the exact
     * same input, precisely because one rounds immediately (correct for a
     * single stored transaction) and the other defers rounding (correct
     * for summing many rows). Neither is "more correct" in isolation — each
     * is correct for its own calculation family. Using the wrong family for
     * a given context is the mistake this architecture exists to prevent.
     */
    public function test_transaction_safe_and_analytics_only_results_can_differ_before_final_rounding(): void
    {
        $amount = 31423.99;
        $rate = 0.04;

        $transactionSafeBase = TaxCalculationService::extractTaxFromInclusiveAmount($amount, $rate)->baseAmount;
        $analyticsRawBase = TaxCalculationService::extractBaseFromInclusiveAmountRaw($amount, $rate);

        // Both are numerically close, but not required to be bit-identical —
        // the transaction-safe value is already rounded; the analytics value
        // is not. Rounding the analytics value should match the transaction
        // value for a single row (they diverge only once many rows are summed).
        $this->assertSame($transactionSafeBase, round($analyticsRawBase, 2));
        $this->assertNotSame($transactionSafeBase, $analyticsRawBase, 'The raw analytics value should not already equal the rounded transaction value.');
    }
}
