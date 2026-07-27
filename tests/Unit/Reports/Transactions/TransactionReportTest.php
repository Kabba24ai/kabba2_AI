<?php

namespace Tests\Unit\Reports\Transactions;

use App\Services\Reports\Transactions\TransactionEnumerator;
use App\Services\Reports\Transactions\TransactionReport;
use App\Services\Reports\SalesReportingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Transaction Report row mapping + totals. Pure — no database. The report must
 * present plain financial fields and must NOT expose Authorize.Net or
 * reconciliation columns.
 */
class TransactionReportTest extends TestCase
{
    private TransactionReport $report;

    protected function setUp(): void
    {
        $this->report = new TransactionReport(new TransactionEnumerator(new SalesReportingService()));
    }

    private function record(array $over = []): array
    {
        return array_merge([
            'payment_date' => '2026-07-20 08:29:50', 'transaction_type' => 'charge',
            'order_number' => '#3292', 'parent_order_number' => '', 'is_extension' => false,
            'customer_name' => 'Shane Harris', 'company' => '', 'payment_method' => 'Card',
            'payment_status' => 'Paid', 'subtotal' => 344.00, 'header_tax' => 33.54,
            'grand_total' => 377.54, 'payment_amount' => 377.54, 'refund_principal' => null,
            'refund_tax' => null, 'signed_amount' => 377.54, 'transaction_id' => '81704815683',
        ], $over);
    }

    public function test_columns_are_plain_financial_no_gateway_or_reconciliation_terms(): void
    {
        $cols = TransactionReport::COLUMNS;
        $blob = strtolower(implode('|', $cols));

        $this->assertStringNotContainsString('authorize', $blob);
        $this->assertStringNotContainsString('reconcil', $blob);
        $this->assertStringNotContainsString('gateway', $blob);
        $this->assertStringNotContainsString('settlement', $blob);
        // Sanity: expected financial columns present.
        $this->assertContains('Grand Total', $cols);
        $this->assertContains('Signed Amount', $cols);
    }

    public function test_maps_a_charge_row(): void
    {
        $row = $this->report->mapRow($this->record());
        $this->assertSame(TransactionReport::COLUMNS, array_keys($row));
        $this->assertSame('Charge', $row['Type']);
        $this->assertSame('#3292', $row['Order Number']);
        $this->assertSame('344.00', $row['Subtotal']);
        $this->assertSame('33.54', $row['Tax']);
        $this->assertSame('377.54', $row['Signed Amount']);
        $this->assertSame('81704815683', $row['Transaction Reference']);
    }

    public function test_refund_signed_amount_is_negative(): void
    {
        $row = $this->report->mapRow($this->record([
            'transaction_type' => 'refund', 'payment_status' => 'Refunded',
            'payment_amount' => 33.54, 'refund_principal' => 0.00, 'refund_tax' => 33.54,
            'signed_amount' => -33.54, 'transaction_id' => '121737576486', 'order_number' => '',
        ]));
        $this->assertSame('Refund', $row['Type']);
        $this->assertSame('-33.54', $row['Signed Amount']);
        $this->assertSame('33.54', $row['Refund Tax']);
    }

    public function test_totals_net_collected_sums_signed_amounts(): void
    {
        $rows = new Collection([
            $this->report->mapRow($this->record(['signed_amount' => 377.54])),
            $this->report->mapRow($this->record(['transaction_type' => 'refund', 'signed_amount' => -33.54])),
            $this->report->mapRow($this->record(['transaction_type' => 'void', 'signed_amount' => 0.0])),
        ]);
        $totals = $this->report->totals($rows);
        $this->assertSame(3, $totals['count']);
        $this->assertSame(344.00, $totals['net_collected']); // 377.54 - 33.54 + 0
    }
}
