<?php

namespace App\Services;

use App\Http\DataObjects\TaxBreakdown;

/**
 * Financial Engine foundation (Phase 2.1), reporting adapter (Phase 2.2 and
 * 2.3), and calculation-pattern reference (Phase 2.3A).
 *
 * The single authoritative place for the tax formulas already proven
 * correct elsewhere in the codebase. This class does not decide which
 * formula applies to a given transaction type (Payment vs. Charge vs.
 * Refund vs. Discount) — that decision requires a business-confirmed
 * truth table (see docs/financial-engine-consolidation/PHASE_2_0_SIGNOFF_READINESS.md
 * §4.1-4.2) and is deliberately out of scope for this class until then.
 *
 * ── Two calculation families (see docs/financial-engine-consolidation/
 *    PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md for the full policy) ──
 *
 * TRANSACTION-SAFE methods return a rounded TaxBreakdown ready to store or
 * display for ONE financial transaction. Use these when creating or
 * presenting an invoice, payment, charge, refund, or discount record —
 * anywhere the result becomes (or reflects) a source-of-truth value:
 *   - extractTaxFromInclusiveAmount()
 *   - addTaxToExclusiveAmount()
 *
 * ANALYTICS-ONLY methods return unrounded/raw values or SQL fragments,
 * intended ONLY for aggregating many rows before a single final rounding
 * (dashboards, KPI totals, trend reports). Never store an analytics-only
 * result as a transaction's persisted tax/base amount — these methods
 * never create or modify a financial transaction, and their output is not
 * a complete, storable value for a single record:
 *   - extractTaxFromInclusiveAmountSql()
 *   - extractBaseFromInclusiveAmountRaw()
 *
 * A transaction-safe method's rounded output CAN still be correct for an
 * analytics consumer, when that consumer's aggregation pattern already
 * rounds per-row before summing (e.g. PaymentReconciliationLedger, which
 * rounded per-row before this class existed — see below). The reverse is
 * never true: an analytics-only method's raw output must not be treated
 * as a finished, storable transaction value.
 *
 * As of Phase 2.2, callers are SalesReportEngineV2 (via the SQL-expression
 * builder below) and PaymentReconciliationLedger (via extractTaxFromInclusiveAmount()
 * directly — an analytics consumer correctly using the transaction-safe
 * method, because its own aggregation already rounds per row). SalesTaxReportEngine::accountRows()
 * is deliberately NOT a caller yet — its per-row values are summed unrounded
 * across streams before a single final rounding, which this class's
 * always-rounded TaxBreakdown contract cannot reproduce without changing
 * that report's totals. Per Financial Decision FD-001, SalesTaxReportEngine
 * is now officially deferred to a future Sales Tax Report V2 project — see
 * docs/financial-engine-consolidation/PHASE_2_2_COMPLETION_REPORT.md and
 * docs/financial-engine-consolidation/FINANCIAL_DECISIONS.md.
 *
 * As of Phase 2.3, Dashboard\IndexController::getRevenueRows() also calls
 * extractBaseFromInclusiveAmountRaw() — the same aggregation-order
 * consideration applies there, which is why that method exists as an
 * unrounded, analytics-only companion rather than reusing the
 * transaction-safe TaxBreakdown.
 * See docs/financial-engine-consolidation/PHASE_2_3_COMPLETION_REPORT.md.
 */
class TaxCalculationService
{
    /**
     * TRANSACTION-SAFE. Returns a rounded TaxBreakdown suitable for storing
     * or displaying a single financial transaction (invoice, payment,
     * charge, refund, discount). See the class docblock for the
     * transaction-safe vs. analytics-only distinction.
     *
     * Back out the tax portion of an amount that already includes tax.
     *
     * Use this when the stored amount is tax-inclusive (e.g. a Payment's
     * `customer_accounts.amount`, which is the full amount received).
     * This is the division formula already used by SalesReportEngineV2,
     * SalesTaxReportEngine, PaymentReconciliationLedger, and the credit-tab
     * Blade views: base = amount / (1 + rate), tax = amount - base.
     *
     * The Dashboard "Last Payment" bug (fixed) and the still-open Dashboard
     * revenue/tax reporting bug (docs/financial-engine-consolidation/
     * PHASE_2_0_SIGNOFF_READINESS.md §2.2) are both instances of this
     * formula being needed but a different (multiplication) formula being
     * used instead.
     *
     * Both base and tax are rounded once, from the full-precision
     * intermediate values, not from each other — rounding the base first
     * and then deriving tax from the already-rounded base (as an earlier
     * version of this method did) can disagree with the production
     * formula by one cent on rare inputs. See
     * docs/financial-engine-consolidation/PHASE_2_2_COMPLETION_REPORT.md
     * for the discovery and the exhaustive equivalence proof.
     *
     * @param  float  $amount  The tax-inclusive amount, in dollars.
     * @param  float  $rate  The tax rate as a decimal (e.g. 0.0887 for 8.87%).
     */
    public static function extractTaxFromInclusiveAmount(float $amount, float $rate): TaxBreakdown
    {
        if ($rate <= 0) {
            return new TaxBreakdown(
                baseAmount: $amount,
                taxAmount: 0.0,
                totalAmount: $amount,
            );
        }

        $baseRaw = $amount / (1 + $rate);
        $taxRaw = $amount - $baseRaw;

        return new TaxBreakdown(
            baseAmount: round($baseRaw, 2),
            taxAmount: round($taxRaw, 2),
            totalAmount: $amount,
        );
    }

    /**
     * TRANSACTION-SAFE. Returns a rounded TaxBreakdown suitable for storing
     * or displaying a single financial transaction. See the class docblock
     * for the transaction-safe vs. analytics-only distinction.
     *
     * Add tax on top of an amount that does not yet include tax.
     *
     * Use this when the stored amount is tax-exclusive (e.g. a manual
     * Charge's `customer_accounts.amount`, which is entered pre-tax).
     * This is the multiplication formula already used by
     * `_additional_charges.blade.php`: tax = amount * rate,
     * total = amount + tax.
     *
     * @param  float  $amount  The tax-exclusive amount, in dollars.
     * @param  float  $rate  The tax rate as a decimal (e.g. 0.0887 for 8.87%).
     */
    public static function addTaxToExclusiveAmount(float $amount, float $rate): TaxBreakdown
    {
        if ($rate <= 0) {
            return new TaxBreakdown(
                baseAmount: $amount,
                taxAmount: 0.0,
                totalAmount: $amount,
            );
        }

        $tax = round($amount * $rate, 2);
        $total = round($amount + $tax, 2);

        return new TaxBreakdown(
            baseAmount: $amount,
            taxAmount: $tax,
            totalAmount: $total,
        );
    }

    /**
     * ANALYTICS-ONLY. Do not use this to compute or store a single
     * transaction's tax amount — it returns raw, unrounded SQL fragments
     * intended solely for aggregating many rows before one final rounding.
     * Never creates or modifies a financial transaction. See the class
     * docblock for the transaction-safe vs. analytics-only distinction.
     *
     * SQL form of extractTaxFromInclusiveAmount(), for reporting engines that
     * aggregate the formula across many rows at the database level for
     * performance (e.g. SalesReportEngineV2). Returns raw SQL fragments —
     * the caller is responsible for wrapping them in SUM()/AS as needed.
     *
     * This produces the exact same CASE expression already duplicated
     * across SalesReportEngineV2::queryAccountPayments() and
     * ::queryDailyAccountPayments() — centralizing the text, not changing
     * it. Rounding is intentionally NOT applied here: the SQL SUM()
     * aggregates full-precision per-row values, matching the existing
     * "sum first, round once at display" behavior of every current caller.
     *
     * @param  string  $amountColumn  Column or expression holding the tax-inclusive amount.
     * @param  string  $rateColumn  Column or expression holding the tax rate (0-1 decimal).
     * @return array{base: string, tax: string}
     */
    public static function extractTaxFromInclusiveAmountSql(string $amountColumn = 'amount', string $rateColumn = 'sales_tax'): array
    {
        $rateExpr = "CAST(NULLIF(COALESCE({$rateColumn}, '0'), '') AS DECIMAL(10,6))";

        return [
            'base' => "CASE WHEN {$rateExpr} > 0 THEN {$amountColumn} / (1 + {$rateExpr}) ELSE {$amountColumn} END",
            'tax' => "CASE WHEN {$rateExpr} > 0 THEN {$amountColumn} - {$amountColumn} / (1 + {$rateExpr}) ELSE 0 END",
        ];
    }

    /**
     * ANALYTICS-ONLY. Do not use this to compute or store a single
     * transaction's base amount — the unrounded output is not a finished,
     * storable value. Never creates or modifies a financial transaction.
     * See the class docblock for the transaction-safe vs. analytics-only
     * distinction.
     *
     * Raw (unrounded) form of extractTaxFromInclusiveAmount()'s base-amount
     * calculation, for PHP callers that must SUM many rows' base amounts
     * before rounding once at the end — e.g. a dashboard period total
     * grouped and summed across many payment rows.
     *
     * extractTaxFromInclusiveAmount() always returns cent-rounded values,
     * which is correct for displaying a single transaction but WRONG for
     * this use case: rounding each row before summing can shift the final
     * total away from what SalesReportEngineV2 computes for the same data
     * (which sums this exact raw formula in SQL, at full precision, before
     * any rounding happens). Discovered and proven during Phase 2.3 — see
     * docs/financial-engine-consolidation/PHASE_2_3_COMPLETION_REPORT.md.
     *
     * Callers should round only after summing, matching every other
     * "sum first, round once at display" caller in the codebase.
     *
     * @param  float  $amount  The tax-inclusive amount, in dollars.
     * @param  float  $rate  The tax rate as a decimal (e.g. 0.0887 for 8.87%).
     */
    public static function extractBaseFromInclusiveAmountRaw(float $amount, float $rate): float
    {
        return $rate > 0 ? $amount / (1 + $rate) : $amount;
    }
}
