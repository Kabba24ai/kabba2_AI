<?php

namespace Tests\Unit\Reports\PaymentReconciliation;

use App\Services\Reports\PaymentReconciliation\AuthorizeNetTransactionParser;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationExport;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationMatcher;
use App\Services\Reports\PaymentReconciliation\PaymentReconciliationSummary;
use App\Services\Reports\SalesReportingService;
use App\Services\Reports\Transactions\TransactionEnumerator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Phase 2: derived Reconciliation Status, Match Confidence, dashboard summary,
 * health banner, quick filters, and the exceptions-only export. Pure — no DB.
 */
class PaymentReconciliationPhase2Test extends TestCase
{
    private PaymentReconciliationExport $export;
    private PaymentReconciliationSummary $summary;
    private array $g;

    protected function setUp(): void
    {
        $parser = new AuthorizeNetTransactionParser();
        $this->export = new PaymentReconciliationExport(new TransactionEnumerator(new SalesReportingService()), $parser, new PaymentReconciliationMatcher());
        $this->summary = new PaymentReconciliationSummary();

        $raw = file_get_contents(dirname(__DIR__, 3) . '/Fixtures/PaymentReconciliation/authnet_sample.txt');
        $this->g = $parser->indexByTransactionId($parser->parse($raw))['byId'];
    }

    private function gw(string $id): array { return $this->g[$id][0]; }

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

    // ── Reconciliation Status ────────────────────────────────────────────────

    /** @dataProvider statusScenarios */
    public function test_reconciliation_status(string $expected, array $row): void
    {
        $this->assertSame($expected, $row['Reconciliation Status']);
    }

    public static function statusScenarios(): array
    {
        // Built lazily inside the test via a fresh instance would be ideal, but
        // dataProviders run before setUp — so build rows here with local deps.
        $p = new AuthorizeNetTransactionParser();
        $exp = new PaymentReconciliationExport(new TransactionEnumerator(new SalesReportingService()), $p, new PaymentReconciliationMatcher());
        $raw = file_get_contents(dirname(__DIR__, 3) . '/Fixtures/PaymentReconciliation/authnet_sample.txt');
        $g = $p->indexByTransactionId($p->parse($raw))['byId'];
        $k = fn (array $o = []) => array_merge([
            'transaction_id' => '81704815683', 'order_number' => '#3292', 'is_extension' => false,
            'parent_order_number' => '', 'reference_order_number' => '', 'deleted' => false,
            'subtotal' => 344.00, 'header_tax' => 33.54, 'line_tax' => 33.54, 'grand_total' => 377.54,
            'payment_amount' => 377.54, 'signed_amount' => 377.54, 'transaction_type' => 'charge',
            'is_collected' => true, 'customer_external_id' => 'CUS-DID6-M3Z9', 'customer_name' => 'Shane Harris',
            'first_name' => 'Shane', 'last_name' => 'Harris', 'email' => 's.harris06@yahoo.com', 'tax_exempt' => false,
        ], $o);

        return [
            'exact match'         => ['Exact Match', $exp->buildRow($k(), $g['81704815683'][0])],
            'missing in kabba'    => ['Missing in Kabba', $exp->gatewayOnlyRow($g['121736787529'][0])],
            'missing in authnet'  => ['Missing in Authorize.Net', $exp->buildRow($k(), null)],
            'amount mismatch'     => ['Amount Mismatch', $exp->buildRow($k(['transaction_id' => '81673810699', 'order_number' => '#2899-A']), $g['81673810699'][0])],
            'duplicate'           => ['Duplicate Transaction', $exp->buildRow($k(['transaction_id' => '81700000042', 'order_number' => '#3399', 'payment_amount' => 222.00, 'signed_amount' => 222.00, 'grand_total' => 222.00, 'header_tax' => 0.0, 'subtotal' => 222.00]), $g['81700000042'][0], ['duplicate_gateway_id' => true])],
            'declined not exception' => ['Exact Match', $exp->buildRow($k(['transaction_id' => '81799999999', 'transaction_type' => 'decline', 'is_collected' => false, 'signed_amount' => 0.0, 'payment_amount' => 100.00]), null)],
            'tax difference'      => ['Tax Difference', $exp->buildRow($k(['line_tax' => 30.00]), $g['81704815683'][0])],
            'parent/child'        => ['Parent/Child Conflict', $exp->buildRow($k(['is_extension' => true, 'parent_order_number' => '#2899', 'reference_order_number' => '#3000', 'line_tax' => null]), $g['81704815683'][0])],
            'deleted extension'   => ['Deleted Extension', $exp->buildRow($k(['is_extension' => true, 'deleted' => true, 'parent_order_number' => '#2899', 'reference_order_number' => '#2899', 'line_tax' => null]), $g['81704815683'][0])],
            'manual review (gt+tax)' => ['Manual Review Required', $exp->buildRow($k(['transaction_id' => '81673810699', 'order_number' => '#2899-A', 'is_extension' => true, 'parent_order_number' => '#2899', 'reference_order_number' => '#2899', 'line_tax' => null, 'payment_amount' => 411.08, 'signed_amount' => 411.08]), $g['81673810699'][0])],
        ];
    }

    // ── Match Confidence ─────────────────────────────────────────────────────

    public function test_confidence_full_match_is_100(): void
    {
        $row = $this->export->buildRow($this->kabba(), $this->gw('81704815683'));
        $this->assertSame('100%', $row['Match Confidence']);
    }

    public function test_confidence_rounding_only_is_95(): void
    {
        // 2-cent difference: beyond the 1-cent match tolerance, still rounding.
        $g = $this->gw('81704815683');
        $g['Settlement Amount'] = '377.56';
        $row = $this->export->buildRow($this->kabba(), $g);
        $this->assertSame('No', $row['Payment Amount Match']);
        $this->assertSame('95%', $row['Match Confidence']);
    }

    public function test_confidence_invoice_and_amount_without_txn_id_is_75(): void
    {
        // No Transaction ID on the Kabba side → cannot match by id; invoice+amount do.
        $row = $this->export->buildRow($this->kabba(['transaction_id' => '']), $this->gw('81704815683'));
        $this->assertSame('75%', $row['Match Confidence']);
    }

    public function test_confidence_zero_without_counterpart(): void
    {
        $row = $this->export->buildRow($this->kabba(), null);
        $this->assertSame('0%', $row['Match Confidence']);
    }

    // ── Dashboard summary + health banner ────────────────────────────────────

    public function test_summary_empty_is_green(): void
    {
        $s = $this->summary->fromRows([]);
        $this->assertSame('green', $s['health']);
        $this->assertSame('🟢 All transactions reconciled.', $s['banner']);
        $this->assertSame(0, $s['exceptions']);
    }

    public function test_summary_fully_reconciled_is_green(): void
    {
        $rows = new Collection([
            $this->export->buildRow($this->kabba(), $this->gw('81704815683')),
            $this->export->buildRow($this->kabba(['transaction_id' => '81704763750', 'order_number' => '', 'payment_amount' => 289.74, 'signed_amount' => 289.74, 'grand_total' => 289.74, 'header_tax' => 0.0, 'subtotal' => 289.74, 'line_tax' => 0.0]), $this->gw('81704763750')),
        ]);
        $s = $this->summary->fromRows($rows);
        $this->assertSame('green', $s['health']);
        $this->assertSame(2, $s['exact_matches']);
        $this->assertSame(2, $s['total_gateway']);
        $this->assertSame(2, $s['total_kabba']);
    }

    public function test_summary_with_every_exception_type_is_red(): void
    {
        $rows = $this->everyExceptionRows();
        $s = $this->summary->fromRows($rows);

        $this->assertSame('red', $s['health']);
        $this->assertStringContainsString('reconciliation', $s['banner']);
        $this->assertGreaterThanOrEqual(1, $s['amount_mismatches']);
        $this->assertGreaterThanOrEqual(1, $s['missing_in_kabba']);
        $this->assertGreaterThanOrEqual(1, $s['missing_in_authorizenet']);
        $this->assertGreaterThanOrEqual(1, $s['duplicate_transaction_ids']);
    }

    public function test_summary_warnings_only_is_yellow(): void
    {
        $rows = new Collection([
            $this->export->buildRow($this->kabba(), $this->gw('81704815683')),               // exact
            $this->export->buildRow($this->kabba(['line_tax' => 30.00]), $this->gw('81704815683')), // tax difference (warning)
        ]);
        $s = $this->summary->fromRows($rows);
        $this->assertSame('yellow', $s['health']);
        $this->assertSame(1, $s['exceptions']);
        $this->assertStringContainsString('requires review', $s['banner']);
    }

    // ── Quick filters + exceptions export ────────────────────────────────────

    public function test_exceptions_only_export_excludes_exact_matches(): void
    {
        $rows = $this->everyExceptionRows();
        $exceptions = $this->export->onlyExceptions($rows);

        $this->assertGreaterThan(0, $exceptions->count());
        foreach ($exceptions as $r) {
            $this->assertNotSame('Exact Match', $r['Reconciliation Status']);
        }
    }

    public function test_quick_filter_selects_by_status(): void
    {
        $rows = $this->everyExceptionRows();

        $amt = $this->export->applyQuickFilter($rows, 'amount_mismatch');
        $this->assertGreaterThan(0, $amt->count());
        foreach ($amt as $r) {
            $this->assertSame('Amount Mismatch', $r['Reconciliation Status']);
        }

        $this->assertSame($rows->count(), $this->export->applyQuickFilter($rows, 'all')->count());
        $this->assertSame(
            $rows->filter(fn ($r) => $r['Reconciliation Status'] !== 'Exact Match')->count(),
            $this->export->applyQuickFilter($rows, 'exceptions')->count()
        );
    }

    /** One row of every major status, including an Exact Match. */
    private function everyExceptionRows(): Collection
    {
        $k = fn (array $o = []) => $this->kabba($o);

        return new Collection([
            $this->export->buildRow($k(), $this->gw('81704815683')),                                                              // Exact Match
            $this->export->buildRow($k(['transaction_id' => '81673810699', 'order_number' => '#2899-A']), $this->gw('81673810699')), // Amount Mismatch
            $this->export->gatewayOnlyRow($this->gw('121736787529')),                                                             // Missing in Kabba
            $this->export->buildRow($k(), null),                                                                                   // Missing in Authorize.Net
            $this->export->buildRow($k(['transaction_id' => '81700000042', 'order_number' => '#3399', 'payment_amount' => 222.00, 'signed_amount' => 222.00, 'grand_total' => 222.00, 'header_tax' => 0.0, 'subtotal' => 222.00]), $this->gw('81700000042'), ['duplicate_gateway_id' => true]), // Duplicate
            $this->export->buildRow($k(['line_tax' => 30.00]), $this->gw('81704815683')),                                          // Tax Difference
        ]);
    }
}
