<?php

namespace App\Services\Reports\PaymentReconciliation;

use App\Enums\Orders\OrderPaymentStatus;
use App\Services\Reports\SalesReportingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Payment Reconciliation export: one row per transaction attempt,
 * approved payment, refund, or void — never a netted order row. The leading
 * columns mirror the Authorize.Net transaction download exactly (see
 * ReconciliationColumns), then Kabba internal + reconciliation columns follow.
 *
 * Two modes:
 *   - Kabba-only (no gateway file): enumerate Kabba payment records, populate
 *     the AuthNet-compatible columns from Kabba where available (blank for
 *     gateway-only fields — never synthesized), and flag Missing Gateway.
 *   - Reconciled (gateway file supplied): additionally match each Kabba record
 *     to its Authorize.Net row by Transaction ID (then Invoice/Order Number),
 *     populate the AuthNet columns from the authoritative gateway row, and emit
 *     gateway-only rows for transactions with no Kabba counterpart.
 *
 * Row composition (buildRow/gatewayOnlyRow/normalize helpers) is PURE and unit-
 * tested; only enumerateKabbaRecords() touches the database.
 */
class PaymentReconciliationExport
{
    public function __construct(
        private SalesReportingService $dates,
        private AuthorizeNetTransactionParser $parser,
        private PaymentReconciliationMatcher $matcher,
    ) {}

    /**
     * @return Collection<int,array<string,string>> rows keyed by ReconciliationColumns::all()
     */
    public function rows(array $filters, ?string $gatewayFileContents = null): Collection
    {
        $kabba = $this->enumerateKabbaRecords($filters);

        // Multiple Kabba records sharing one gateway id (should be 1:1).
        $kabbaByTxn = $kabba->filter(fn ($r) => ($r['transaction_id'] ?? '') !== '')
            ->groupBy('transaction_id');
        $multiKabbaIds = $kabbaByTxn->filter(fn ($g) => $g->count() > 1)->keys()->all();

        $gatewayRows = [];
        $gatewayIndex = ['byId' => [], 'duplicates' => []];
        if ($gatewayFileContents !== null && trim($gatewayFileContents) !== '') {
            $gatewayRows  = $this->parser->parse($gatewayFileContents);
            $gatewayIndex = $this->parser->indexByTransactionId($gatewayRows);
        }

        $out = collect();
        $matchedGatewayIds = [];

        foreach ($kabba as $k) {
            $txn = (string) ($k['transaction_id'] ?? '');
            $gateway = null;
            if ($txn !== '' && isset($gatewayIndex['byId'][$txn])) {
                $gateway = $gatewayIndex['byId'][$txn][0];
                $matchedGatewayIds[$txn] = true;
            }

            $flags = [
                'duplicate_gateway_id' => $txn !== '' && in_array($txn, $gatewayIndex['duplicates'], true),
                'multiple_kabba'       => $txn !== '' && in_array($txn, $multiKabbaIds, true),
                'gateway_only'         => false,
            ];

            $out->push($this->buildRow($k, $gateway, $flags));
        }

        // Gateway transactions with no Kabba counterpart → gateway-only rows.
        foreach ($gatewayIndex['byId'] as $txn => $group) {
            if (isset($matchedGatewayIds[$txn])) {
                continue;
            }
            foreach ($group as $g) {
                $out->push($this->gatewayOnlyRow($g, [
                    'duplicate_gateway_id' => in_array($txn, $gatewayIndex['duplicates'], true),
                ]));
            }
        }

        return $out->values();
    }

    /** Quick-filter chips for the report UI (key => label). Canonical list. */
    public static function filterOptions(): array
    {
        return [
            'all'             => 'All',
            'exact'           => 'Exact Matches',
            'exceptions'      => 'Exceptions Only',
            'amount_mismatch' => 'Amount Mismatches',
            'missing_kabba'   => 'Missing in Kabba',
            'missing_authnet' => 'Missing in Authorize.Net',
            'duplicates'      => 'Duplicate Transactions',
            'refund_problems' => 'Refund Problems',
            'tax_problems'    => 'Tax Problems',
            'manual_review'   => 'Manual Review Required',
        ];
    }

    /**
     * Apply a quick filter to built rows by their canonical Reconciliation
     * Status (and, for two views, a diagnostic flag). Pure. Unknown/empty/'all'
     * returns every row.
     *
     * @param  Collection<int,array<string,string>> $rows
     */
    public function applyQuickFilter(Collection $rows, ?string $filter): Collection
    {
        $status = fn ($r) => $r['Reconciliation Status'] ?? '';

        return match ($filter) {
            null, '', 'all'   => $rows->values(),
            'exceptions'      => $rows->filter(fn ($r) => $status($r) !== 'Exact Match')->values(),
            'exact'           => $rows->filter(fn ($r) => $status($r) === 'Exact Match')->values(),
            'amount_mismatch' => $rows->filter(fn ($r) => $status($r) === 'Amount Mismatch')->values(),
            'missing_kabba'   => $rows->filter(fn ($r) => $status($r) === 'Missing in Kabba')->values(),
            'missing_authnet' => $rows->filter(fn ($r) => $status($r) === 'Missing in Authorize.Net')->values(),
            'refund_problems' => $rows->filter(fn ($r) => $status($r) === 'Refund Mismatch')->values(),
            'tax_problems'    => $rows->filter(fn ($r) => $status($r) === 'Tax Difference')->values(),
            'duplicates'      => $rows->filter(fn ($r) => ($r['Duplicate Gateway Transaction ID'] ?? 'No') === 'Yes')->values(),
            'manual_review'   => $rows->filter(fn ($r) => ($r['Manual Review Required'] ?? 'No') === 'Yes')->values(),
            default           => $rows->values(),
        };
    }

    /** Exceptions-only subset (everything not an Exact Match) — for the daily export. */
    public function onlyExceptions(Collection $rows): Collection
    {
        return $this->applyQuickFilter($rows, 'exceptions');
    }

    // ── PURE row composition (unit-tested) ──────────────────────────────────

    /**
     * Compose a full export row for a Kabba record with an optional matched
     * gateway row. AuthNet columns come from the gateway row when present
     * (authoritative), else the few Kabba-derivable values with the rest blank.
     *
     * @param  array<string,mixed> $k
     * @param  array<string,string>|null $g
     */
    public function buildRow(array $k, ?array $g, array $flags = []): array
    {
        $authnet = $g !== null ? $this->authnetFromGateway($g) : $this->authnetFromKabba($k);
        $kabbaCols = $this->kabbaColumns($k);
        $recon = $this->matcher->reconcile($k, $g, $flags);

        return array_merge($authnet, $kabbaCols, $recon);
    }

    /** A transaction present in the gateway file but absent from Kabba. */
    public function gatewayOnlyRow(array $g, array $flags = []): array
    {
        $authnet   = $this->authnetFromGateway($g);
        $kabbaCols = $this->blankKabbaColumns();
        $recon     = $this->matcher->reconcile($this->emptyKabbaRecord($g), $g, array_merge($flags, ['gateway_only' => true]));

        return array_merge($authnet, $kabbaCols, $recon);
    }

    /** AuthNet columns straight from the authoritative gateway row (verbatim). */
    private function authnetFromGateway(array $g): array
    {
        $row = [];
        foreach (ReconciliationColumns::AUTHNET as $col) {
            $row[$col] = (string) ($g[$col] ?? '');
        }

        return $row;
    }

    /**
     * AuthNet-compatible columns derived from Kabba when no gateway row is
     * matched. Only the values Kabba actually holds are populated; every
     * gateway-specific field is left blank (never synthesized).
     */
    private function authnetFromKabba(array $k): array
    {
        $row = array_fill_keys(ReconciliationColumns::AUTHNET, '');

        $type   = $k['transaction_type'] ?? 'charge';
        $magnitude = number_format(abs((float) ($k['payment_amount'] ?? $k['signed_amount'] ?? 0)), 2, '.', '');

        $row['Response Code']    = ($type === 'decline') ? '2' : '1';
        $row['Action Code']      = match ($type) { 'refund' => 'CREDIT', 'void' => 'VOID', default => 'AUTH_CAPTURE' };
        $row['Transaction ID']   = (string) ($k['transaction_id'] ?? '');
        $row['Invoice Number']   = (string) ($k['order_number'] ?? '');
        $row['Order Number']     = (string) ($k['order_number'] ?? '');
        // Refunds/voids: Authorize.Net reports amounts POSITIVE; void may be 0.
        $row['Total Amount']      = $type === 'void' ? '0.00' : $magnitude;
        $row['Settlement Amount'] = $type === 'void' ? '0.00' : $magnitude;
        $row['Customer ID']       = (string) ($k['customer_external_id'] ?? '');
        $row['Customer First Name'] = (string) ($k['first_name'] ?? '');
        $row['Customer Last Name']  = (string) ($k['last_name'] ?? '');
        $row['Company']           = (string) ($k['company'] ?? '');
        $row['Email']             = (string) ($k['email'] ?? '');
        $row['Submit Date/Time']  = (string) ($k['payment_date'] ?? '');
        $row['L2 - Tax']          = ''; // gateway L2 tax is not sent by Kabba — leave blank, never synthesize
        $row['L2 - Tax Exempt']   = ($k['tax_exempt'] ?? false) ? 'Yes' : 'No';

        return $row;
    }

    /** @param array<string,mixed> $k */
    private function kabbaColumns(array $k): array
    {
        return [
            'Kabba Source Table'          => (string) ($k['source_table'] ?? ''),
            'Kabba Source Record ID'      => (string) ($k['source_record_id'] ?? ''),
            'Kabba Order ID'              => (string) ($k['order_id'] ?? ''),
            'Kabba Order Number'          => (string) ($k['order_number'] ?? ''),
            'Kabba Parent Order Number'   => (string) ($k['parent_order_number'] ?? ''),
            'Kabba Reference Order Number'=> (string) ($k['reference_order_number'] ?? ''),
            'Kabba Is Extension'          => ($k['is_extension'] ?? false) ? 'Yes' : 'No',
            'Kabba Order Date'            => (string) ($k['order_date'] ?? ''),
            'Kabba Payment Date'          => (string) ($k['payment_date'] ?? ''),
            'Kabba Recorded Date'         => (string) ($k['recorded_date'] ?? ''),
            'Kabba Deleted Status'        => ($k['deleted'] ?? false) ? 'Deleted' : 'Active',
            'Kabba Payment Status'        => (string) ($k['payment_status'] ?? ''),
            'Kabba Payment Method'        => (string) ($k['payment_method'] ?? ''),
            'Kabba Subtotal'              => $this->num($k['subtotal'] ?? null),
            'Kabba Header Tax'            => $this->num($k['header_tax'] ?? null),
            'Kabba Line Tax'             => $this->num($k['line_tax'] ?? null),
            'Kabba Grand Total'           => $this->num($k['grand_total'] ?? null),
            'Kabba Payment Amount'        => $this->num($k['payment_amount'] ?? null),
            'Kabba Refund Principal'      => $this->num($k['refund_principal'] ?? null),
            'Kabba Refund Tax'            => $this->num($k['refund_tax'] ?? null),
            'Kabba Signed Amount'         => $this->num($k['signed_amount'] ?? null),
            'Kabba Allocated Tax'         => $this->num($k['allocated_tax'] ?? null),
            'Kabba Derived Pre-Tax Amount'=> $this->num($k['derived_pretax'] ?? null),
            'Kabba Billing Charge Base'   => $this->num($k['billing_base'] ?? null),
            'Kabba Billing Charge Tax'    => $this->num($k['billing_tax'] ?? null),
            'Kabba Billing Charge Total'  => $this->num($k['billing_total'] ?? null),
            'Kabba Tax Rate'              => ($k['tax_rate'] ?? null) === null ? '' : number_format((float) $k['tax_rate'], 5, '.', ''),
            'Kabba Tax Exempt'            => ($k['tax_exempt'] ?? false) ? 'Yes' : 'No',
        ];
    }

    private function blankKabbaColumns(): array
    {
        return array_fill_keys(ReconciliationColumns::KABBA, '');
    }

    /** A minimal Kabba record shell so the matcher can score a gateway-only row. */
    private function emptyKabbaRecord(array $g): array
    {
        return [
            'transaction_type' => (fn () => match (strtoupper(trim((string) ($g['Action Code'] ?? '')))) {
                'CREDIT' => 'refund', 'VOID' => 'void',
                default  => trim((string) ($g['Response Code'] ?? '')) === '2' ? 'decline' : 'charge',
            })(),
            'signed_amount' => 0.0,
            'line_tax'      => null,
        ];
    }

    private function num($v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return number_format((float) $v, 2, '.', '');
    }

    // ── DB enumeration (not unit-tested here — no sandbox DB) ────────────────

    /**
     * One normalized Kabba record per order_payment attempt in the window
     * (settled charges, refunds, voids, and stored declines). Anchored on the
     * payment/refund event date. Extension context and billing base/tax are
     * enriched from the linked order + billing charge.
     *
     * @return Collection<int,array<string,mixed>>
     */
    private function enumerateKabbaRecords(array $filters): Collection
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
            $status = OrderPaymentStatus::tryFrom($r->status);
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
