<?php

namespace App\Services\Reports\PaymentReconciliation;

/**
 * Computes the reconciliation columns for one export row from a normalized
 * Kabba record and an OPTIONAL matched Authorize.Net gateway row.
 *
 * Deliberately pure and dependency-free (no DB, no framework) so every rule is
 * unit-testable against fixtures. All money comparisons use integer cents to
 * stay immune to binary-float noise — the same discipline the payment code uses
 * elsewhere. Outputs are export-ready strings ('Yes'/'No'/'' or 2dp numbers).
 *
 * Key detector: "Grand Total Plus Tax Signature" flags the tax-added-twice
 * pattern (collected ≈ grand_total + header_tax, where grand_total already
 * includes tax) — the $411.08 = $377.54 + $33.54 case.
 */
class PaymentReconciliationMatcher
{
    private const EPS_CENTS = 1; // 1-cent tolerance

    /**
     * @param  array<string,mixed> $k     Normalized Kabba record (see PaymentReconciliationExport::normalize()).
     * @param  array<string,string>|null $g Matched Authorize.Net row (assoc by header), or null.
     * @param  array{duplicate_gateway_id?:bool, multiple_kabba?:bool, gateway_only?:bool} $flags
     * @return array<string,string> Keyed by ReconciliationColumns::RECONCILIATION names.
     */
    public function reconcile(array $k, ?array $g, array $flags = []): array
    {
        $gatewayOnly = (bool) ($flags['gateway_only'] ?? false);
        $hasGateway  = $g !== null;

        // Amounts. Kabba "collected" magnitude = |signed_amount| (payment or refund).
        $kabbaAmount   = $this->cents(abs((float) ($k['signed_amount'] ?? 0)));
        $gatewaySettle = $hasGateway ? $this->cents($this->money($g['Settlement Amount'] ?? ($g['Total Amount'] ?? '0'))) : null;

        // Field-level matches — blank ('') when either side is empty (nothing to
        // compare), 'Yes'/'No' only when both are present.
        $txnIdMatch  = $this->fieldMatch($hasGateway, (string) ($k['transaction_id'] ?? ''), $hasGateway ? (string) ($g['Transaction ID'] ?? '') : '');
        $invMatch    = $this->fieldMatch($hasGateway, $this->stripHash((string) ($k['order_number'] ?? '')), $hasGateway ? $this->stripHash((string) ($g['Invoice Number'] ?? '')) : '');
        $custIdMatch = $this->fieldMatch($hasGateway, (string) ($k['customer_external_id'] ?? ''), $hasGateway ? (string) ($g['Customer ID'] ?? '') : '');
        $nameMatch   = $this->fieldMatch($hasGateway, $this->norm((string) ($k['customer_name'] ?? '')), $hasGateway ? $this->norm(trim(($g['Customer First Name'] ?? '') . ' ' . ($g['Customer Last Name'] ?? ''))) : '');
        $emailMatch  = $this->fieldMatch($hasGateway, $this->norm((string) ($k['email'] ?? '')), $hasGateway ? $this->norm((string) ($g['Email'] ?? '')) : '');
        $amtMatch    = $this->tri($hasGateway && $gatewaySettle !== null, fn () => abs($kabbaAmount - $gatewaySettle) <= self::EPS_CENTS);
        $typeMatch   = $this->tri($hasGateway, fn () => $this->gatewayType($g) === ($k['transaction_type'] ?? ''));

        $amountDiff  = ($hasGateway && $gatewaySettle !== null)
            ? number_format(($gatewaySettle - $kabbaAmount) / 100, 2, '.', '')
            : '';
        $dateDiff    = ($hasGateway) ? $this->dateDiffDays((string) ($k['payment_date'] ?? ''), (string) ($g['Submit Date/Time'] ?? '')) : '';

        // Grand-total-plus-tax (double-tax) signature. grand_total already
        // includes header_tax; a collected amount ≈ grand_total + header_tax is
        // the tax-added-twice fingerprint. Evaluate against whichever collected
        // figure exists (gateway settlement preferred, else Kabba amount).
        $collected   = $gatewaySettle ?? $kabbaAmount;
        $grandTotal  = $this->cents((float) ($k['grand_total'] ?? 0));
        $headerTax   = $this->cents((float) ($k['header_tax'] ?? 0));
        $gtPlusTax   = ($grandTotal > 0 && $headerTax > 0 && abs($collected - ($grandTotal + $headerTax)) <= self::EPS_CENTS);

        // Over/under collection (magnitude vs stored grand total). Only meaningful
        // for charges; refunds/voids are exempt.
        $isCharge    = ($k['transaction_type'] ?? '') === 'charge';
        $exceeds     = $isCharge && $grandTotal > 0 && ($collected - $grandTotal) > self::EPS_CENTS;
        $below       = $isCharge && $grandTotal > 0 && ($grandTotal - $collected) > self::EPS_CENTS;

        // Extension integrity.
        $isExtension = (bool) ($k['is_extension'] ?? false);
        $deletedExt  = $isExtension && (bool) ($k['deleted'] ?? false);
        $linkConflict = $isExtension && $this->parentChildConflict($k);

        // Tax discrepancies.
        $lineTax     = $k['line_tax'];
        $taxDiff     = ($lineTax === null || $lineTax === '')
            ? ''
            : number_format(((float) $k['header_tax']) - ((float) $lineTax), 2, '.', '');
        $refundDiff  = $this->refundDifference($k, $g);

        // Match status + method.
        $matchMethod = 'None';
        if ($hasGateway) {
            $matchMethod = ($txnIdMatch === 'Yes') ? 'Transaction ID' : (($invMatch === 'Yes') ? 'Invoice Number' : 'None');
        }
        $matchStatus = $this->matchStatus($gatewayOnly, $hasGateway, $amtMatch, $typeMatch, $k);

        // Manual-review escalation + notes.
        $notes = [];
        if ($gtPlusTax)      { $notes[] = 'Grand-total-plus-tax overcharge signature (tax added twice).'; }
        if ($exceeds)        { $notes[] = 'Collected amount exceeds stored order grand total.'; }
        if ($below)          { $notes[] = 'Collected amount below stored order grand total (partial or short-pay).'; }
        if ($deletedExt)     { $notes[] = 'Deleted extension transaction with a settled payment.'; }
        if ($linkConflict)   { $notes[] = 'Parent/child extension linkage conflict.'; }
        if ($hasGateway && $amtMatch === 'No') { $notes[] = 'Gateway settlement does not match Kabba amount.'; }
        if ($hasGateway && $typeMatch === 'No') { $notes[] = 'Transaction type differs between gateway and Kabba.'; }
        if (!empty($flags['duplicate_gateway_id'])) { $notes[] = 'Duplicate gateway Transaction ID.'; }
        if (!empty($flags['multiple_kabba'])) { $notes[] = 'Multiple Kabba records share this Transaction ID.'; }
        if ($matchStatus === 'Kabba Only (Missing Gateway)' && $isCharge && ($k['is_collected'] ?? false)) {
            $notes[] = 'Settled charge has no matching gateway transaction.';
        }
        if ($taxDiff !== '' && abs((float) $taxDiff) > 0.005) { $notes[] = 'Header tax differs from summed line tax.'; }

        $manualReviewBool =
            $gtPlusTax || $exceeds || $deletedExt || $linkConflict
            || ($hasGateway && ($amtMatch === 'No' || $typeMatch === 'No'))
            || !empty($flags['duplicate_gateway_id'])
            || ($taxDiff !== '' && abs((float) $taxDiff) > 0.005);
        $manualReview = $this->yesno($manualReviewBool);

        // Derived, single-value operator fields — computed HERE (the one
        // canonical place) from the flags above, never recomputed downstream.
        $isCollected  = (bool) ($k['is_collected'] ?? false);
        $refundMismatch = ($k['transaction_type'] ?? '') === 'refund'
            && $refundDiff !== '' && abs((float) $refundDiff) > 0.005;

        $isRefund = ($k['transaction_type'] ?? '') === 'refund';
        $primaryStatus = $this->primaryStatus([
            'missing_kabba'    => $gatewayOnly,
            'duplicate'        => !empty($flags['duplicate_gateway_id']),
            'missing_gateway'  => !$gatewayOnly && !$hasGateway,
            'is_collected'     => $isCollected,
            'is_refund'        => $isRefund,
            'amount_mismatch'  => $hasGateway && $amtMatch === 'No',
            'refund_mismatch'  => $refundMismatch,
            'link_conflict'    => $linkConflict,
            'deleted_ext'      => $deletedExt,
            'tax_diff'         => $taxDiff !== '' && abs((float) $taxDiff) > 0.005,
            'manual_review'    => $manualReviewBool,
            'reconciled'       => $hasGateway && $amtMatch === 'Yes' && $typeMatch === 'Yes',
        ]);

        $confidence = $this->matchConfidence($hasGateway, $gatewayOnly, $txnIdMatch, $invMatch, $amtMatch, $nameMatch, $custIdMatch, $amountDiff, $dateDiff);

        return [
            'Reconciliation Status'            => $primaryStatus,
            'Match Confidence'                 => $confidence . '%',
            'Match Status'                     => $matchStatus,
            'Match Method'                     => $matchMethod,
            'Amount Difference'                => $amountDiff,
            'Date Difference Days'             => $dateDiff,
            'Transaction ID Match'             => $txnIdMatch,
            'Invoice Number Match'             => $invMatch,
            'Customer ID Match'                => $custIdMatch,
            'Customer Name Match'              => $nameMatch,
            'Email Match'                      => $emailMatch,
            'Payment Amount Match'             => $amtMatch,
            'Transaction Type Match'           => $typeMatch,
            'Duplicate Gateway Transaction ID' => $this->yesno(!empty($flags['duplicate_gateway_id'])),
            'Multiple Kabba Records'           => $this->yesno(!empty($flags['multiple_kabba'])),
            'Missing Gateway Transaction'      => $this->yesno(!$gatewayOnly && !$hasGateway),
            'Missing Kabba Transaction'        => $this->yesno($gatewayOnly),
            'Grand Total Plus Tax Signature'   => $this->yesno($gtPlusTax),
            'Payment Exceeds Order Total'      => $this->yesno($exceeds),
            'Payment Below Order Total'        => $this->yesno($below),
            'Deleted Extension Transaction'    => $this->yesno($deletedExt),
            'Parent Child Link Conflict'       => $this->yesno($linkConflict),
            'Tax Difference'                   => $taxDiff,
            'Refund Difference'                => $refundDiff,
            'Manual Review Required'           => $manualReview,
            'Reconciliation Notes'             => implode(' ', $notes),
        ];
    }

    /**
     * The single primary Reconciliation Status for a row, chosen from the
     * already-computed flags by a fixed priority (most-actionable first) so
     * every row gets exactly one status. The detailed diagnostic columns are
     * unchanged; this is a summary of them.
     *
     * @param array<string,bool> $f
     */
    private function primaryStatus(array $f): string
    {
        return match (true) {
            $f['missing_kabba']                       => 'Missing in Kabba',
            $f['duplicate']                           => 'Duplicate Transaction',
            $f['missing_gateway'] && $f['is_collected'] => 'Missing in Authorize.Net',
            // A refund whose amount doesn't reconcile is a Refund Mismatch, not
            // a generic Amount Mismatch (the two coincide numerically).
            $f['amount_mismatch'] && $f['is_refund']  => 'Refund Mismatch',
            $f['amount_mismatch']                     => 'Amount Mismatch',
            $f['refund_mismatch']                     => 'Refund Mismatch',
            $f['link_conflict']                       => 'Parent/Child Conflict',
            $f['deleted_ext']                         => 'Deleted Extension',
            $f['tax_diff']                            => 'Tax Difference',
            $f['manual_review']                       => 'Manual Review Required',
            $f['reconciled']                          => 'Exact Match',
            // A non-collected attempt (decline/void) with no gateway counterpart
            // has nothing to settle — not an exception.
            $f['missing_gateway'] && !$f['is_collected'] => 'Exact Match',
            default                                   => 'Unclassified',
        };
    }

    /**
     * Match Confidence (0–100) — how reliably this Kabba row is tied to its
     * Authorize.Net row. Scoring (highest applicable tier wins):
     *   100 — Transaction ID matches, amount matches, and invoice/order matches.
     *    95 — Transaction ID matches; amount differs only by rounding (≤ $0.02).
     *    90 — Transaction ID matches and amount matches (no invoice confirmation).
     *    75 — Invoice/order number matches and amount matches (no Transaction ID).
     *    50 — Customer matches, amount matches, and dates are within 1 day.
     *     0 — no gateway counterpart, or no reliable match.
     */
    private function matchConfidence(
        bool $hasGateway, bool $gatewayOnly,
        string $txn, string $inv, string $amt, string $name, string $cust,
        string $amountDiff, string $dateDiff
    ): int {
        if (!$hasGateway || $gatewayOnly) {
            return 0; // no counterpart to score against
        }

        $roundingOnly = $amt === 'No' && $amountDiff !== '' && abs((float) $amountDiff) <= 0.02;
        $dateClose    = $dateDiff !== '' && (int) $dateDiff <= 1;

        return match (true) {
            $txn === 'Yes' && $amt === 'Yes' && $inv === 'Yes' => 100,
            $txn === 'Yes' && $roundingOnly                    => 95,
            $txn === 'Yes' && $amt === 'Yes'                   => 90,
            $inv === 'Yes' && $amt === 'Yes'                   => 75,
            ($name === 'Yes' || $cust === 'Yes') && $amt === 'Yes' && $dateClose => 50,
            default                                            => 0,
        };
    }

    private function matchStatus(bool $gatewayOnly, bool $hasGateway, string $amtMatch, string $typeMatch, array $k): string
    {
        if ($gatewayOnly) {
            return 'Gateway Only (Missing Kabba)';
        }
        if (!$hasGateway) {
            // A declined attempt legitimately has no settled gateway money.
            return ($k['transaction_type'] ?? '') === 'decline'
                ? 'Declined (No Settlement)'
                : 'Kabba Only (Missing Gateway)';
        }
        if ($amtMatch === 'No') {
            return 'Amount Mismatch';
        }
        if ($typeMatch === 'No') {
            return 'Type Mismatch';
        }

        return 'Matched';
    }

    private function refundDifference(array $k, ?array $g): string
    {
        if (($k['transaction_type'] ?? '') !== 'refund') {
            return '';
        }
        // Kabba refund magnitude vs gateway refund magnitude (when both known).
        $kabbaRefund = abs((float) ($k['signed_amount'] ?? 0));
        if ($g === null) {
            return '';
        }
        $gatewayRefund = $this->money($g['Settlement Amount'] ?? ($g['Total Amount'] ?? '0'));

        return number_format($gatewayRefund - $kabbaRefund, 2, '.', '');
    }

    private function parentChildConflict(array $k): bool
    {
        $parent    = $this->stripHash((string) ($k['parent_order_number'] ?? ''));
        $reference = $this->stripHash((string) ($k['reference_order_number'] ?? ''));

        // An extension must carry a parent/reference link, and when both are
        // present they must agree.
        if ($parent === '' && $reference === '') {
            return true;
        }
        if ($parent !== '' && $reference !== '' && $parent !== $reference) {
            return true;
        }

        return false;
    }

    private function gatewayType(array $g): string
    {
        $action = strtoupper(trim((string) ($g['Action Code'] ?? '')));
        return match ($action) {
            'CREDIT'       => 'refund',
            'VOID'         => 'void',
            'AUTH_CAPTURE' => trim((string) ($g['Response Code'] ?? '')) === '2' ? 'decline' : 'charge',
            default        => 'charge',
        };
    }

    // ── small pure helpers ──────────────────────────────────────────────────

    private function cents(float $v): int
    {
        return (int) round($v * 100);
    }

    private function money(string $v): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', $v);
    }

    private function norm(string $v): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $v)));
    }

    private function stripHash(string $v): string
    {
        return ltrim(trim($v), '#');
    }

    private function yesno(bool $v): string
    {
        return $v ? 'Yes' : 'No';
    }

    /** Yes/No when comparable, '' when there is nothing to compare. */
    private function tri(bool $comparable, callable $test): string
    {
        return $comparable ? ($test() ? 'Yes' : 'No') : '';
    }

    /**
     * Field-level match: '' when there is no gateway row or either value is
     * blank (nothing to compare), else 'Yes'/'No' by case-insensitive equality.
     */
    private function fieldMatch(bool $hasGateway, string $a, string $b): string
    {
        if (!$hasGateway || trim($a) === '' || trim($b) === '') {
            return '';
        }

        return strcasecmp(trim($a), trim($b)) === 0 ? 'Yes' : 'No';
    }

    private function dateDiffDays(string $kabbaDate, string $gatewayDate): string
    {
        $a = strtotime($kabbaDate);
        $b = strtotime($gatewayDate);
        if ($a === false || $b === false || $kabbaDate === '' || $gatewayDate === '') {
            return '';
        }

        return (string) (int) round(abs($a - $b) / 86400);
    }
}
