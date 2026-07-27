<?php

namespace App\Services\Reports\Transactions;

use App\Enums\Orders\OrderPaymentStatus;
use App\Services\Reports\SalesReportingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The shared transaction engine. Produces one normalized Kabba transaction
 * record per order_payment attempt in the window (settled charges, refunds,
 * voids, and stored declines), anchored on the payment/refund event date, with
 * extension context and billing base/tax enriched from the linked order +
 * billing charge.
 *
 * This is the single source both consumers reuse:
 *   - Transaction Report (TransactionReport) — renders these records directly.
 *   - Authorize.Net Reconciliation (PaymentReconciliationExport) — matches them
 *     against an uploaded Authorize.Net settlement file.
 *
 * The record shape is a plain associative array; downstream code reads keys by
 * name (see the return array below). Only this class touches the database.
 */
class TransactionEnumerator
{
    public function __construct(private SalesReportingService $dates) {}

    /**
     * @return Collection<int,array<string,mixed>>
     */
    public function records(array $filters): Collection
    {
        [$start, $end] = $this->dates->resolveDateRange($filters);
        if (!$start || !$end) {
            return collect();
        }

        $rows = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereBetween(
                DB::raw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at))'),
                [$start->toDateString(), $end->toDateString()]
            )
            ->when(!empty($filters['store']), function ($q) use ($filters) {
                $q->whereExists(function ($sub) use ($filters) {
                    $sub->selectRaw('1')->from('order_products as orp')
                        ->whereColumn('orp.order_id', 'o.id')->whereNull('orp.deleted_at')
                        ->where(fn ($s) => $s->where('orp.delivery_store_id', $filters['store'])
                            ->orWhere('orp.pickup_store_id', $filters['store']));
                });
            })
            ->select([
                'op.id as payment_id', 'op.order_id', 'op.transaction_id', 'op.amount',
                'op.status', 'op.payment_method', 'op.payment_datetime', 'op.refunded_at',
                'op.refund_amount', 'op.tax_refunded', 'op.created_at as op_created_at', 'op.deleted_at as op_deleted_at',
                'o.order_number', 'o.reference_order_number', 'o.unique_id as order_unique_id',
                'o.order_date', 'o.subtotal', 'o.tax_amount as header_tax', 'o.grand_total',
                'o.customer_name', 'o.customer_email', 'o.company_name', 'o.is_tax_exempt', 'o.deleted_at as order_deleted_at',
            ])
            ->orderBy('op.id')
            ->get();

        $orderIds = $rows->pluck('order_id')->unique()->filter()->values();

        $lineTax = $orderIds->isEmpty() ? collect() : DB::table('order_products')
            ->whereIn('order_id', $orderIds)->whereNull('deleted_at')
            ->selectRaw('order_id, SUM(tax) as line_tax')->groupBy('order_id')->pluck('line_tax', 'order_id');

        $billing = $orderIds->isEmpty() ? collect() : DB::table('billing_charges')
            ->whereIn('child_order_id', $orderIds)
            ->where('billing_charge_type', 'extension')
            ->selectRaw('child_order_id, SUM(amount) as base, SUM(tax_amount) as tax')
            ->groupBy('child_order_id')->get()->keyBy('child_order_id');

        return $rows->map(function ($r) use ($lineTax, $billing) {
            $type = match ($r->status) {
                'Refunded', 'Partial Refund' => 'refund',
                'Voided'                     => 'void',
                'Failed'                     => 'decline',
                'Pending'                    => 'pending',
                default                      => 'charge',
            };

            $amount    = (float) $r->amount;
            $refund    = (float) ($r->refund_amount ?? 0);
            $refundTax = (float) ($r->tax_refunded ?? 0);
            $grand     = (float) $r->grand_total;
            $headerTax = (float) $r->header_tax;

            $paymentAmount = match ($type) {
                'refund' => $refund,
                'void'   => 0.0,
                default  => $amount,
            };
            $signed = match ($type) {
                'charge' => $amount,
                'refund' => -$refund,
                default  => 0.0,                 // void / decline / pending: no collected money
            };
            $allocatedTax = ($grand > 0.0) ? round($headerTax * ($paymentAmount / $grand), 2) : 0.0;

            $ln = array_key_exists($r->order_id, $lineTax->toArray()) ? (float) $lineTax[$r->order_id] : null;
            $bc = $billing->get($r->order_id);
            $isExtension = !empty($r->reference_order_number) || str_contains((string) $r->order_number, '-');

            $name = trim((string) $r->customer_name);
            $parts = preg_split('/\s+/', $name, 2);

            return [
                'source_table'          => 'order_payments',
                'source_record_id'      => (int) $r->payment_id,
                'transaction_id'        => (string) ($r->transaction_id ?? ''),
                'order_id'              => (int) $r->order_id,
                'order_number'          => (string) $r->order_number,
                'parent_order_number'   => (string) ($r->reference_order_number ?? ''),
                'reference_order_number'=> (string) ($r->reference_order_number ?? ''),
                'is_extension'          => $isExtension,
                'order_date'            => (string) $r->order_date,
                'payment_date'          => (string) ($r->refunded_at ?? $r->payment_datetime ?? $r->op_created_at),
                'recorded_date'         => (string) $r->op_created_at,
                'deleted'               => $r->op_deleted_at !== null || $r->order_deleted_at !== null,
                'payment_status'        => (string) $r->status,
                'payment_method'        => (string) ($r->payment_method ?? ''),
                'subtotal'              => (float) $r->subtotal,
                'header_tax'            => $headerTax,
                'line_tax'              => $ln,
                'grand_total'           => $grand,
                'payment_amount'        => $paymentAmount,
                'refund_principal'      => $type === 'refund' ? round($refund - $refundTax, 2) : null,
                'refund_tax'            => $type === 'refund' ? $refundTax : null,
                'signed_amount'         => $signed,
                'allocated_tax'         => $allocatedTax,
                'derived_pretax'        => round($paymentAmount - $allocatedTax, 2),
                'billing_base'          => $bc?->base,
                'billing_tax'           => $bc?->tax,
                'billing_total'         => $bc ? round((float) $bc->base + (float) $bc->tax, 2) : null,
                'tax_rate'              => ((float) $r->subtotal) > 0 ? round($headerTax / (float) $r->subtotal, 5) : null,
                'tax_exempt'            => strtolower((string) $r->is_tax_exempt) === 'yes',
                'transaction_type'      => $type === 'pending' ? 'charge' : $type,
                'is_collected'          => in_array($r->status, ['Paid', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other', 'Partial Payment'], true),
                'customer_external_id'  => '', // order_payments carry no gateway Customer ID; blank, never synthesized
                'customer_name'         => $name,
                'first_name'            => $parts[0] ?? '',
                'last_name'             => $parts[1] ?? '',
                'company'               => (string) ($r->company_name ?? ''),
                'email'                 => (string) ($r->customer_email ?? ''),
            ];
        })
        // Pending placeholders that never became a real attempt (no gateway id,
        // never collected) are not transaction attempts — exclude them.
        ->reject(fn ($k) => $k['payment_status'] === 'Pending' && $k['transaction_id'] === '')
        ->values();
    }
}
