<?php

namespace App\Services\Reports\Transactions;

use Illuminate\Support\Collection;

/**
 * Transaction Report — a general financial report listing Kabba transactions
 * for a period (one row per charge, refund, or void). It reuses the shared
 * TransactionEnumerator and presents the records with plain financial column
 * names. It intentionally references neither Authorize.Net nor reconciliation —
 * that is a separate tool.
 *
 * Row mapping (mapRow) is pure and unit-tested; only the enumerator touches the
 * database.
 */
class TransactionReport
{
    /** Report columns (online table + CSV header), in order. */
    public const COLUMNS = [
        'Date',
        'Type',
        'Order Number',
        'Parent Order',
        'Extension',
        'Customer',
        'Company',
        'Payment Method',
        'Status',
        'Subtotal',
        'Tax',
        'Grand Total',
        'Payment Amount',
        'Refund Principal',
        'Refund Tax',
        'Signed Amount',
        'Transaction Reference',
    ];

    public function __construct(private TransactionEnumerator $enumerator) {}

    /**
     * @return Collection<int,array<string,string>> rows keyed by self::COLUMNS
     */
    public function rows(array $filters): Collection
    {
        return $this->enumerator->records($filters)->map(fn ($r) => $this->mapRow($r))->values();
    }

    /** Pure record → report row. */
    public function mapRow(array $r): array
    {
        return [
            'Date'                  => (string) ($r['payment_date'] ?? ''),
            'Type'                  => $this->typeLabel((string) ($r['transaction_type'] ?? 'charge')),
            'Order Number'          => (string) ($r['order_number'] ?? ''),
            'Parent Order'          => (string) ($r['parent_order_number'] ?? ''),
            'Extension'             => ($r['is_extension'] ?? false) ? 'Yes' : 'No',
            'Customer'              => trim((string) ($r['customer_name'] ?? '')) ?: '—',
            'Company'               => (string) ($r['company'] ?? ''),
            'Payment Method'        => (string) ($r['payment_method'] ?? ''),
            'Status'                => (string) ($r['payment_status'] ?? ''),
            'Subtotal'              => $this->num($r['subtotal'] ?? null),
            'Tax'                   => $this->num($r['header_tax'] ?? null),
            'Grand Total'           => $this->num($r['grand_total'] ?? null),
            'Payment Amount'        => $this->num($r['payment_amount'] ?? null),
            'Refund Principal'      => $this->num($r['refund_principal'] ?? null),
            'Refund Tax'            => $this->num($r['refund_tax'] ?? null),
            'Signed Amount'         => $this->num($r['signed_amount'] ?? null),
            'Transaction Reference' => (string) ($r['transaction_id'] ?? ''),
        ];
    }

    /**
     * Period totals for the summary strip. Net Collected sums the signed amount
     * (charges positive, refunds negative; voids/declines contribute zero).
     *
     * @param  Collection<int,array<string,string>> $rows mapped rows
     * @return array{count:int, net_collected:float}
     */
    public function totals(Collection $rows): array
    {
        return [
            'count'         => $rows->count(),
            'net_collected' => round($rows->sum(fn ($r) => (float) $r['Signed Amount']), 2),
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'charge'  => 'Charge',
            'refund'  => 'Refund',
            'void'    => 'Void',
            'decline' => 'Declined',
            default   => ucfirst($type),
        };
    }

    private function num($v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return number_format((float) $v, 2, '.', '');
    }
}
