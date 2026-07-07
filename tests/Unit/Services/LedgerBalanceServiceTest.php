<?php

namespace Tests\Unit\Services;

use App\Services\LedgerBalanceService;
use PHPUnit\Framework\TestCase;

class LedgerBalanceServiceTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // Implemented types: each test proves the result matches the exact
    // production formula in CustomHelper::updateCreditBalance(), reproduced
    // inline below from the source (re-verified directly during Phase 2.6,
    // not assumed) — not a re-statement of what LedgerBalanceService itself
    // does, which would prove nothing.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Production 'payment' case: $amountWithTax = $record->amount, always —
     * regardless of taxable status. $newBalance -= $amountWithTax.
     */
    public function test_payment_taxable_matches_production_amount_withheld(): void
    {
        $amount = 150.00;
        $rate = 0.0887;

        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_PAYMENT,
            $amount,
            taxRate: $rate,
            customerTaxable: true,
        );

        // Production: $amountWithTax = $record->amount = 150.00, unaffected by rate.
        $this->assertSame(150.00, $result->totalAmount);
    }

    public function test_payment_non_taxable_matches_production_amount_withheld(): void
    {
        $amount = 150.00;

        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_PAYMENT,
            $amount,
            taxRate: 0.0887, // must be ignored — production ignores the rate for non-taxable customers
            customerTaxable: false,
        );

        // Production: $amountWithTax = $record->amount = 150.00 either way; tax breakdown shows $0 tax.
        $this->assertSame(150.00, $result->totalAmount);
        $this->assertSame(0.0, $result->taxAmount);
    }

    /**
     * Production 'charge' case, sales_tax_type='add':
     * $amountWithTax = $record->amount + $record->amount * $record->sales_tax.
     * $newBalance += $amountWithTax.
     */
    public function test_charge_add_matches_production_amount_added(): void
    {
        $amount = 100.00;
        $rate = 0.0887;

        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_CHARGE,
            $amount,
            taxRate: $rate,
            salesTaxType: 'add',
        );

        // Production: 100.00 + (100.00 * 0.0887) = 108.87
        $this->assertSame(108.87, $result->totalAmount);
    }

    /**
     * Production 'charge' case, sales_tax_type='reverse':
     * $amountWithTax = $record->amount (unchanged) — tax is already included.
     */
    public function test_charge_reverse_matches_production_amount_unchanged(): void
    {
        $amount = 150.00;
        $rate = 0.0887;

        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_CHARGE,
            $amount,
            taxRate: $rate,
            salesTaxType: 'reverse',
        );

        // Production: $amountWithTax = $record->amount = 150.00, unchanged.
        $this->assertSame(150.00, $result->totalAmount);
    }

    /**
     * Production 'charge' case, sales_tax_type='free' or null/anything else:
     * $amountWithTax = $record->amount (unchanged), sales_tax = 0.
     */
    public function test_charge_free_matches_production_amount_unchanged(): void
    {
        $amount = 100.00;

        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_CHARGE,
            $amount,
            taxRate: 0.0887, // must be ignored for this branch, matching production
            salesTaxType: 'free',
        );

        $this->assertSame(100.00, $result->totalAmount);
        $this->assertSame(0.0, $result->taxAmount);
    }

    public function test_charge_with_null_sales_tax_type_matches_production_default_branch(): void
    {
        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_CHARGE,
            100.00,
            taxRate: 0.0887,
            salesTaxType: null,
        );

        $this->assertSame(100.00, $result->totalAmount);
        $this->assertSame(0.0, $result->taxAmount);
    }

    /**
     * Production 'order' case:
     * $newBalance += $record->amount + $externalTaxAmount — no rate-based
     * formula; tax is entirely caller-supplied.
     */
    public function test_order_matches_production_amount_plus_external_tax(): void
    {
        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_ORDER,
            200.00,
            externalTaxAmount: 17.74,
        );

        $this->assertSame(200.00, $result->baseAmount);
        $this->assertSame(17.74, $result->taxAmount);
        $this->assertSame(217.74, $result->totalAmount);
    }

    public function test_order_with_zero_external_tax(): void
    {
        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_ORDER,
            200.00,
        );

        $this->assertSame(200.00, $result->totalAmount);
        $this->assertSame(0.0, $result->taxAmount);
    }

    /**
     * Extension\StoreController's current inline formula:
     * `$validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00`.
     * salesTaxType='add' models add_tax=true.
     */
    public function test_extension_with_tax_matches_controller_formula(): void
    {
        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_EXTENSION,
            120.00,
            taxRate: 0.05,
            salesTaxType: 'add',
        );

        // Production: round(120.00 * 0.05, 2) = 6.00
        $this->assertSame(6.00, $result->taxAmount);
        $this->assertSame(126.00, $result->totalAmount);
    }

    public function test_extension_without_tax_matches_controller_formula(): void
    {
        $result = LedgerBalanceService::amountWithTax(
            LedgerBalanceService::TYPE_EXTENSION,
            120.00,
            taxRate: 0.05, // must be ignored — add_tax is false
            salesTaxType: null,
        );

        $this->assertSame(0.0, $result->taxAmount);
        $this->assertSame(120.00, $result->totalAmount);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Deferred types: each must throw, never silently compute a guessed
    // result. One test per named deferred type, plus one for a wholly
    // unrecognized type (covering the mission's hypothetical future types,
    // none of which have a real string identifier in this codebase).
    // ─────────────────────────────────────────────────────────────────────

    public function test_refund_throws_and_is_not_silently_computed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/refund/i');

        LedgerBalanceService::amountWithTax('refund', 100.00, 0.0887);
    }

    public function test_discount_throws_and_is_not_silently_computed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/discount/i');

        LedgerBalanceService::amountWithTax('discount', 100.00, 0.0887);
    }

    public function test_credit_throws_and_is_not_silently_computed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/credit/i');

        LedgerBalanceService::amountWithTax('credit', 100.00);
    }

    public function test_debit_throws_and_is_not_silently_computed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/debit/i');

        LedgerBalanceService::amountWithTax('debit', 100.00);
    }

    public function test_account_invoice_throws_and_is_not_silently_computed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/account_invoice/i');

        LedgerBalanceService::amountWithTax('account_invoice', 100.00);
    }

    /**
     * Covers the mission's explicitly out-of-scope hypothetical types
     * (Promotional Credit, Bad Debt, Settlement Adjustments, Payment Plans)
     * — none of which have a real string identifier anywhere in this
     * codebase, so they all fall through to the generic default branch.
     */
    public function test_unrecognized_type_throws_via_default_branch(): void
    {
        $this->expectException(\LogicException::class);

        LedgerBalanceService::amountWithTax('bad_debt', 100.00);
    }

    // ─────────────────────────────────────────────────────────────────────
    // applyTransaction() fail-fast guards. These do not require a database
    // — the type check happens before any DB::transaction() is opened, so
    // an unsupported CustomerAccount never reaches that point. The actual
    // write-path equivalence for supported types (Payment/Order/Charge) is
    // validated separately, against a real database, in
    // docs/financial-engine-consolidation/PHASE_2_7_COMPLETION_REPORT.md —
    // tests/Unit has no database connection to exercise here.
    // ─────────────────────────────────────────────────────────────────────

    public function test_apply_transaction_rejects_refund_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'refund', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/refund/i');

        LedgerBalanceService::applyTransaction($record);
    }

    public function test_apply_transaction_rejects_discount_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'discount', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);

        LedgerBalanceService::applyTransaction($record);
    }

    public function test_apply_transaction_rejects_credit_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'credit', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);

        LedgerBalanceService::applyTransaction($record);
    }

    public function test_apply_transaction_rejects_debit_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'debit', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);

        LedgerBalanceService::applyTransaction($record);
    }

    public function test_apply_transaction_rejects_account_invoice_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'account_invoice', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);

        LedgerBalanceService::applyTransaction($record);
    }

    /**
     * TYPE_EXTENSION is a real amountWithTax() type but is never a real
     * customer_accounts.type value, so applyTransaction() must reject it
     * exactly like any other unsupported type — never silently no-op.
     */
    public function test_apply_transaction_rejects_extension_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'extension', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/TYPE_EXTENSION/');

        LedgerBalanceService::applyTransaction($record);
    }

    public function test_apply_transaction_rejects_unrecognized_type_before_touching_the_database(): void
    {
        $record = new \App\Models\Customers\CustomerAccount(['type' => 'bad_debt', 'amount' => 100.00]);

        $this->expectException(\LogicException::class);

        LedgerBalanceService::applyTransaction($record);
    }
}
