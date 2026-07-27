<?php

namespace Tests\Unit\Reports\PaymentReconciliation;

use App\Services\Reports\PaymentReconciliation\AuthorizeNetTransactionParser;
use App\Services\Reports\PaymentReconciliation\ReconciliationColumns;
use PHPUnit\Framework\TestCase;

/**
 * Parser + column-parity tests against a fixture that mirrors the authoritative
 * Authorize.Net export header and representative rows. Pure — no database.
 */
class AuthorizeNetTransactionParserTest extends TestCase
{
    private AuthorizeNetTransactionParser $parser;
    private string $raw;

    protected function setUp(): void
    {
        $this->parser = new AuthorizeNetTransactionParser();
        $this->raw = file_get_contents(dirname(__DIR__, 3) . '/Fixtures/PaymentReconciliation/authnet_sample.txt');
    }

    public function test_fixture_header_matches_the_authnet_column_contract_exactly(): void
    {
        // Parity guarantee: our AuthNet column list is byte-identical, in order,
        // to the real gateway download header.
        $this->assertSame(ReconciliationColumns::AUTHNET, $this->parser->header($this->raw));
    }

    public function test_parses_every_data_row(): void
    {
        $rows = $this->parser->parse($this->raw);
        $this->assertCount(10, $rows);
        // Each row is keyed by header names and preserves all 45 columns.
        $this->assertSame(ReconciliationColumns::AUTHNET, array_keys($rows[0]));
    }

    public function test_preserves_ids_and_invoice_as_text(): void
    {
        $rows = $this->parser->parse($this->raw);
        $index = $this->parser->indexByTransactionId($rows);

        $this->assertArrayHasKey('81673810699', $index['byId']);
        $this->assertIsString($index['byId']['81673810699'][0]['Transaction ID']);
        // Suffixed extension invoice survives verbatim (no numeric coercion).
        $this->assertSame('#2899-A', $index['byId']['81673810699'][0]['Invoice Number']);
    }

    public function test_transaction_type_classification(): void
    {
        $byId = $this->parser->indexByTransactionId($this->parser->parse($this->raw))['byId'];

        $this->assertTrue($this->parser->isCollectedCharge($byId['81704815683'][0]));  // approved charge
        $this->assertTrue($this->parser->isDeclined($byId['81799999999'][0]));         // response code 2
        $this->assertFalse($this->parser->isCollectedCharge($byId['81799999999'][0])); // decline is not collected
        $this->assertTrue($this->parser->isRefund($byId['121737576486'][0]));          // CREDIT
        $this->assertTrue($this->parser->isVoid($byId['81708368636'][0]));             // VOID
        $this->assertTrue($this->parser->isCardPresent($byId['121736787529'][0]));     // Retail / Card Present
    }

    public function test_detects_duplicate_gateway_transaction_ids(): void
    {
        $index = $this->parser->indexByTransactionId($this->parser->parse($this->raw));
        $this->assertContains('81700000042', $index['duplicates']);
        $this->assertCount(2, $index['byId']['81700000042']);
        // A unique id is not flagged.
        $this->assertNotContains('81704815683', $index['duplicates']);
    }
}
