<?php

namespace App\Services\Reports\PaymentReconciliation;

/**
 * Aggregates built reconciliation rows into the dashboard summary + health
 * banner. Reads ONLY fields the canonical PaymentReconciliationMatcher already
 * produced (primarily "Reconciliation Status") — it does not re-run or
 * duplicate any reconciliation logic. Pure and unit-testable.
 */
class PaymentReconciliationSummary
{
    // Statuses that make the day "red" (hard reconciliation problems).
    private const CRITICAL = [
        'Amount Mismatch', 'Missing in Kabba', 'Missing in Authorize.Net',
        'Duplicate Transaction', 'Refund Mismatch',
    ];

    /**
     * @param  iterable<array<string,string>> $rows rows from PaymentReconciliationExport
     * @return array<string,mixed>
     */
    public function fromRows(iterable $rows): array
    {
        $count = [
            'total_gateway'             => 0,
            'total_kabba'               => 0,
            'exact_matches'             => 0,
            'amount_mismatches'         => 0,
            'missing_in_kabba'          => 0,
            'missing_in_authorizenet'   => 0,
            'duplicate_transaction_ids' => 0,
            'refund_mismatches'         => 0,
            'manual_review_required'    => 0,
        ];
        $exceptions = 0;
        $criticalHit = false;
        $warningHit  = false;

        foreach ($rows as $row) {
            $status = $row['Reconciliation Status'] ?? 'Unclassified';

            if (($row['Missing Gateway Transaction'] ?? 'No') !== 'Yes') { $count['total_gateway']++; }
            if (($row['Missing Kabba Transaction'] ?? 'No') !== 'Yes')   { $count['total_kabba']++; }
            if (($row['Duplicate Gateway Transaction ID'] ?? 'No') === 'Yes') { $count['duplicate_transaction_ids']++; }
            if (($row['Manual Review Required'] ?? 'No') === 'Yes')      { $count['manual_review_required']++; }

            switch ($status) {
                case 'Exact Match':               $count['exact_matches']++; break;
                case 'Amount Mismatch':           $count['amount_mismatches']++; break;
                case 'Missing in Kabba':          $count['missing_in_kabba']++; break;
                case 'Missing in Authorize.Net':  $count['missing_in_authorizenet']++; break;
                case 'Refund Mismatch':           $count['refund_mismatches']++; break;
            }

            if ($status !== 'Exact Match') {
                $exceptions++;
                if (in_array($status, self::CRITICAL, true)) {
                    $criticalHit = true;
                } else {
                    $warningHit = true;
                }
            }
        }

        $health = $criticalHit ? 'red' : ($warningHit ? 'yellow' : 'green');
        $banner = match ($health) {
            'green'  => '🟢 All transactions reconciled.',
            'yellow' => '🟡 ' . $exceptions . ' ' . ($exceptions === 1 ? 'transaction requires' : 'transactions require') . ' review.',
            'red'    => '🔴 ' . $exceptions . ' reconciliation ' . ($exceptions === 1 ? 'issue' : 'issues') . ' detected.',
        };

        return $count + [
            'exceptions' => $exceptions,
            'health'     => $health,
            'banner'     => $banner,
        ];
    }
}
