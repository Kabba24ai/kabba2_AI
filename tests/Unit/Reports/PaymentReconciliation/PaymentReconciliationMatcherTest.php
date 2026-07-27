<?php

namespace Tests\Unit\Reports\PaymentReconciliation;

use App\Services\Reports\PaymentReconciliation\AuthorizeNetTransactionParser;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationExport;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationMatcher;
use App\Services\Reports\PaymentReconciliation\ReconciliationColumns;
use App\Services\Reports\SalesReportingService;
use PHPUnit\Framework\TestCase;

/**
 * Reconciliation-logic + row-composition tests. Pure — no database. Exercises
 * one scenario per requested case: approved charge, declined, credit/refund,
 * void, missing invoice/order, card-present, duplicate, and the
 * grand-total-plus-tax overcharge signature.
 */
class PaymentReconciliationMatcherTest extends TestCase
{
    private PaymentReconciliationExport $export;
    private array $gatewayById;

    protected function setUp(): void
    {
        $parser = new AuthorizeNetTransactionParser();
        $this->export = new PaymentReconciliationExport(new SalesReportingService(), $parser, new PaymentReconciliationMatcher());

        $raw = file_get_contents(dirname(__DIR__, 3) . '/Fixtures/PaymentReconciliation/authnet_sample.txt');
        $this->gatewayById = $parser->indexByTransactionId($parser->parse($raw))['byId'];
    }

    private function g(string $id): array
    {
        return $this->gatewayById[$id][0];
    }

    /** A normalized Kabba charge record; override per scenario. */
    private function kabba(array $over = []): array
    {
        return array_merge([
            'source_table' => 'order_payments', 'source_record_id' => 1,
            'transaction_id' => '81704815683', 'order_id' => 100, 'order_number' => '#3292',
            'parent_order_number' => '', 'reference_order_number' => '', 'is_extension' => false,
            'order_date' => '2026-07-20', 'payment_date' => '2026-07-20 08:29:50', 'recorded_date' => '2026-07-20',
            'deleted' => false, 'payment_status' => 'Paid', 'payment_method' => 'Card',
            'subtotal' => 344.00, 'header_tax' => 33.54, 'line_tax' => 33.54, 'grand_total' => 377.54,
            'payment_amount' => 377.54, 'refund_principal' => null, 'refund_tax' => null, 'signed_amount' => 377.54,
            'allocated_tax' => 33.54, 'derived_pretax' => 344.00, 'billing_base' => null, 'billing_tax' => null,
            'billing_total' => null, 'tax_rate' => 0.0975, 'tax_exempt' => false,
            'transaction_type' => 'charge', 'is_collected' => true,
            'customer_external_id' => 'CUS-DID6-M3Z9', 'customer_name' => 'Shane Harris',
            'first_name' => 'Shane', 'last_name' => 'Harris', 'company' => '', 'email' => 's.harris06@yahoo.com',
        ], $over);
    }

    // ── Column contract ──────────────────────────────────────────────────────

    public function test_column_contract_is_authnet_then_kabba_then_reconciliation(): void
    {
        $all = ReconciliationColumns::all();
        $this->assertCount(45 + 28 + 26, $all); // +2 derived: Reconciliation Status, Match Confidence
        $this->assertSame(ReconciliationColumns::AUTHNET, array_slice($all, 0, 45));
    }

    public function test_built_row_has_every_contract_column(): void
    {
        $row = $this->export->buildRow($this->kabba(), $this->g('81704815683'));
        $this->assertSame(ReconciliationColumns::all(), array_keys($row));
    }

    // ── Scenarios ──────────────────────────────────────────────────────────

    public function test_approved_charge_matches_gateway(): void
    {
        $row = $this->export->buildRow($this->kabba(), $this->g('81704815683'));
        $this->assertSame('Matched', $row['Match Status']);
        $this->assertSame('Transaction ID', $row['Match Method']);
        $this->assertSame('Yes', $row['Payment Amount Match']);
        $this->assertSame('Yes', $row['Transaction Type Match']);
        $this->assertSame('377.54', $row['Settlement Amount']);       // from gateway, positive
        $this->assertSame('377.54', $row['Kabba Signed Amount']);     // positive for a charge
        $this->assertSame('No', $row['Manual Review Required']);
    }

    public function test_declined_charge_is_not_collected_revenue(): void
    {
        $k = $this->kabba([
            'transaction_id' => '81799999999', 'transaction_type' => 'decline',
            'payment_status' => 'Failed', 'is_collected' => false,
            'payment_amount' => 100.00, 'signed_amount' => 0.0, 'order_number' => '#9999',
        ]);
        // Declines have no settled gateway money → no gateway row supplied.
        $row = $this->export->buildRow($k, null);
        $this->assertSame('Declined (No Settlement)', $row['Match Status']);
        $this->assertSame('0.00', $row['Kabba Signed Amount']); // contributes zero to totals
        $this->assertSame('2', $row['Response Code']);           // AuthNet-compatible decline code
    }

    public function test_credit_refund_is_positive_in_authnet_but_negative_signed(): void
    {
        $k = $this->kabba([
            'transaction_id' => '121737576486', 'transaction_type' => 'refund',
            'payment_status' => 'Refunded', 'payment_method' => 'Card', 'order_number' => '',
            'payment_amount' => 33.54, 'refund_principal' => 0.00, 'refund_tax' => 33.54,
            'signed_amount' => -33.54, 'grand_total' => 377.54, 'header_tax' => 33.54,
            'customer_external_id' => 'CUS-HSKD-F2JV', 'customer_name' => 'Tim Sterling',
            'first_name' => 'Tim', 'last_name' => 'Sterling', 'email' => 'timsterling1@yahoo.com',
        ]);
        $row = $this->export->buildRow($k, $this->g('121737576486'));
        $this->assertSame('CREDIT', $row['Action Code']);
        $this->assertSame('33.54', $row['Total Amount']);        // positive (Authorize.Net convention)
        $this->assertSame('-33.54', $row['Kabba Signed Amount']); // negative
        $this->assertSame('Matched', $row['Match Status']);
    }

    public function test_void_shows_zero(): void
    {
        $k = $this->kabba([
            'transaction_id' => '81708368636', 'transaction_type' => 'void',
            'payment_status' => 'Voided', 'payment_amount' => 0.0, 'signed_amount' => 0.0, 'order_number' => '#3350',
        ]);
        $row = $this->export->buildRow($k, $this->g('81708368636'));
        $this->assertSame('VOID', $row['Action Code']);
        $this->assertSame('0.00', $row['Kabba Signed Amount']);
    }

    public function test_missing_invoice_number_still_matches_on_transaction_id(): void
    {
        $k = $this->kabba(['transaction_id' => '81704763750', 'order_number' => '', 'payment_amount' => 289.74, 'signed_amount' => 289.74,
            'grand_total' => 289.74, 'header_tax' => 0.0, 'subtotal' => 289.74, 'tax_rate' => 0.0]);
        $row = $this->export->buildRow($k, $this->g('81704763750'));
        $this->assertSame('Yes', $row['Transaction ID Match']);
        $this->assertSame('', $row['Invoice Number Match']); // blank invoice → not comparable, blank (not "No")
        $this->assertSame('Matched', $row['Match Status']);
    }

    public function test_card_present_row_reconciles_without_customer_or_order(): void
    {
        // Card-present gateway-only transaction (Kabba may hold nothing for it).
        $row = $this->export->gatewayOnlyRow($this->g('121736787529'));
        $this->assertSame('Gateway Only (Missing Kabba)', $row['Match Status']);
        $this->assertSame('Yes', $row['Missing Kabba Transaction']);
        $this->assertSame('418.15', $row['Settlement Amount']);
        $this->assertSame('', $row['Kabba Order Number']);
    }

    public function test_duplicate_gateway_transaction_id_is_flagged(): void
    {
        $k = $this->kabba(['transaction_id' => '81700000042', 'order_number' => '#3399', 'payment_amount' => 222.00, 'signed_amount' => 222.00,
            'grand_total' => 222.00, 'header_tax' => 0.0, 'subtotal' => 222.00, 'tax_rate' => 0.0]);
        $row = $this->export->buildRow($k, $this->g('81700000042'), ['duplicate_gateway_id' => true]);
        $this->assertSame('Yes', $row['Duplicate Gateway Transaction ID']);
        $this->assertSame('Yes', $row['Manual Review Required']);
    }

    public function test_grand_total_plus_tax_signature_and_overcharge(): void
    {
        // The #2899-A case: gateway settled 411.08 = grand_total 377.54 + tax 33.54.
        $k = $this->kabba([
            'transaction_id' => '81673810699', 'order_number' => '#2899-A', 'is_extension' => true,
            'parent_order_number' => '#2899', 'reference_order_number' => '#2899',
            'subtotal' => 344.00, 'header_tax' => 33.54, 'line_tax' => null, 'grand_total' => 377.54,
            'payment_amount' => 411.08, 'signed_amount' => 411.08, 'allocated_tax' => 33.54, 'derived_pretax' => 377.54,
            'customer_external_id' => 'CUS-HSKD-F2JV', 'customer_name' => 'Tim Sterling',
            'first_name' => 'Tim', 'last_name' => 'Sterling', 'email' => 'timsterling1@yahoo.com',
        ]);
        $row = $this->export->buildRow($k, $this->g('81673810699'));

        $this->assertSame('Yes', $row['Grand Total Plus Tax Signature']);
        $this->assertSame('Yes', $row['Payment Exceeds Order Total']);
        $this->assertSame('Yes', $row['Manual Review Required']);
        $this->assertStringContainsString('tax added twice', $row['Reconciliation Notes']);
    }

    public function test_kabba_only_when_no_gateway_match(): void
    {
        $row = $this->export->buildRow($this->kabba(), null);
        $this->assertSame('Kabba Only (Missing Gateway)', $row['Match Status']);
        $this->assertSame('Yes', $row['Missing Gateway Transaction']);
        // AuthNet-compatible columns fall back to Kabba-derived values.
        $this->assertSame('81704815683', $row['Transaction ID']);
        $this->assertSame('377.54', $row['Settlement Amount']);
    }

    public function test_amount_mismatch_between_gateway_and_kabba(): void
    {
        // Kabba recorded 377.54 but gateway settled 411.08 (same txn id).
        $k = $this->kabba(['transaction_id' => '81673810699', 'payment_amount' => 377.54, 'signed_amount' => 377.54, 'order_number' => '#2899-A']);
        $row = $this->export->buildRow($k, $this->g('81673810699'));
        $this->assertSame('Amount Mismatch', $row['Match Status']);
        $this->assertSame('No', $row['Payment Amount Match']);
        $this->assertSame('33.54', $row['Amount Difference']); // 411.08 gateway − 377.54 kabba
    }
}
