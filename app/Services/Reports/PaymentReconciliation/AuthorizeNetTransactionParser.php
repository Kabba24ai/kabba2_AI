<?php

namespace App\Services\Reports\PaymentReconciliation;

/**
 * Parses a standard Authorize.Net transaction download (the tab-delimited
 * export the merchant portal produces — see the authoritative sample
 * Transaction_2026-07-26_204824.txt). Pure and dependency-free so it can be
 * unit-tested against a fixture with no database.
 *
 * Preserves Transaction ID, Invoice Number, and Order Number as TEXT (never
 * casts to int) so long numeric ids and leading zeros survive. Understands the
 * Authorize.Net semantics the reconciliation depends on:
 *   - Response Code 1 = approved, 2 = declined (declines are not collected revenue).
 *   - Action Code AUTH_CAPTURE = charge, CREDIT = refund (exported POSITIVE),
 *     VOID = reversal (amount may be 0.00).
 */
class AuthorizeNetTransactionParser
{
    /**
     * Parse raw export text into an ordered array of associative rows keyed by
     * the file's own header names. Blank lines are skipped; short/long rows are
     * padded/truncated to the header width so column alignment never drifts.
     *
     * @return array<int, array<string,string>>
     */
    public function parse(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        if (count($lines) < 1) {
            return [];
        }

        $header = explode("\t", $lines[0]);
        $width  = count($header);
        $rows   = [];

        foreach (array_slice($lines, 1) as $line) {
            $cells = explode("\t", $line);
            // Normalize width: pad short rows, drop overflow, so header mapping holds.
            $cells = array_slice(array_pad($cells, $width, ''), 0, $width);
            $rows[] = array_combine($header, $cells);
        }

        return $rows;
    }

    /** The header names in file order (for parity checks / diffing). */
    public function header(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                return explode("\t", $line);
            }
        }

        return [];
    }

    /**
     * Index parsed rows by Transaction ID. Because a gateway id should be
     * unique, this also surfaces collisions: the returned 'duplicates' set is
     * every Transaction ID that appeared on more than one row.
     *
     * @param  array<int,array<string,string>> $rows
     * @return array{byId: array<string,array<int,array<string,string>>>, duplicates: array<int,string>}
     */
    public function indexByTransactionId(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $id = (string) ($row['Transaction ID'] ?? '');
            if ($id === '') {
                continue;
            }
            $byId[$id][] = $row;
        }

        // Cast keys back to string — PHP silently coerces numeric-string array
        // keys to int, but gateway Transaction IDs are text.
        $duplicates = array_values(array_map('strval', array_keys(array_filter($byId, fn ($group) => count($group) > 1))));

        return ['byId' => $byId, 'duplicates' => $duplicates];
    }

    // ── Authorize.Net semantics ─────────────────────────────────────────────

    public function isApproved(array $row): bool
    {
        return trim((string) ($row['Response Code'] ?? '')) === '1';
    }

    public function isDeclined(array $row): bool
    {
        return trim((string) ($row['Response Code'] ?? '')) === '2';
    }

    public function isRefund(array $row): bool
    {
        return strtoupper(trim((string) ($row['Action Code'] ?? ''))) === 'CREDIT';
    }

    public function isVoid(array $row): bool
    {
        return strtoupper(trim((string) ($row['Action Code'] ?? ''))) === 'VOID';
    }

    public function isCharge(array $row): bool
    {
        return strtoupper(trim((string) ($row['Action Code'] ?? ''))) === 'AUTH_CAPTURE';
    }

    /** Card-present rows (Retail) often carry no customer/order detail. */
    public function isCardPresent(array $row): bool
    {
        return strtoupper(trim((string) ($row['Product'] ?? ''))) === 'CARD PRESENT'
            || strtoupper(trim((string) ($row['Market Type'] ?? ''))) === 'RETAIL';
    }

    /**
     * Whether this gateway row represents money actually collected (approved,
     * not a decline/void/refund). Refunds are collected-negative and handled
     * separately; declines and voids never count as collected revenue.
     */
    public function isCollectedCharge(array $row): bool
    {
        return $this->isApproved($row) && $this->isCharge($row);
    }
}
