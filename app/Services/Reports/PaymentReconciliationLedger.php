<?php

namespace App\Services\Reports;

use App\Helpers\CustomHelper;
use App\Services\TaxCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Payment Reconciliation Ledger — row-level counterpart to SalesReportEngineV2.
 *
 * Returns one row per payment event across all four revenue streams.
 * The sum of grand_total across all rows reconciles exactly to
 * SalesReportEngineV2::kpis()['total_collected'] for the same filters.
 *
 * Same date anchors as the KPI engine:
 *   Stream A (order payments)   → orders.order_date
 *   Stream B (refunds)          → COALESCE(refunded_at, payment_datetime, created_at)
 *   Stream C (account payments) → customer_accounts.date
 *   Stream D (billing charges)  → billing_charges.paid_at
 */
class PaymentReconciliationLedger
{
    public function __construct(private SalesReportingService $reporting) {}

    public function rows(array $filters): Collection
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        $startDate     = $start->toDateString();
        $endDate       = $end->toDateString();
        $paymentStatus = $filters['payment_status'] ?? 'paid';

        $rows = collect()
            ->concat($this->streamA($filters, $startDate, $endDate))
            ->concat($this->streamB($filters, $startDate, $endDate))
            ->concat(
                in_array($paymentStatus, ['paid', 'all', 'account'])
                    ? $this->streamC($filters, $startDate, $endDate)
                    : collect()
            )
            ->concat(
                !in_array($paymentStatus, ['pod', 'account'])
                    ? $this->streamD($filters, $startDate, $endDate)
                    : collect()
            );

        if (!empty($filters['ledger_source'])) {
            $rows = $rows->filter(fn ($r) => $r->revenue_source_key === $filters['ledger_source']);
        }

        if (!empty($filters['ledger_pm'])) {
            $rows = $rows->filter(fn ($r) => $r->payment_method_key === $filters['ledger_pm']);
        }

        return $rows->sortByDesc('payment_date')->values();
    }

    // ─── Stream A: Standard Order Payments ───────────────────────────────────

    private function streamA(array $filters, string $startDate, string $endDate): Collection
    {
        // Qualifying order IDs from the same base query the KPI engine uses.
        // Anchored on orders.order_date — identical to SalesReportEngineV2.
        $orderIds = $this->reporting->baseQuery($filters)
            ->selectRaw('DISTINCT orders.id')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        // Product type per order to classify revenue source (avoid N+1)
        $productTypes = DB::table('order_products as orp')
            ->join('products as p', 'p.id', '=', 'orp.product_id')
            ->whereIn('orp.order_id', $orderIds)
            ->whereNull('orp.deleted_at')
            ->selectRaw('orp.order_id, GROUP_CONCAT(DISTINCT p.product_type SEPARATOR ",") as types')
            ->groupBy('orp.order_id')
            ->pluck('types', 'order_id');

        return DB::table('orders as o')
            ->join('order_payments as op', function ($join) {
                $join->on('op.order_id', '=', 'o.id')
                    ->whereRaw('op.id = (SELECT MAX(op2.id) FROM order_payments op2 WHERE op2.order_id = o.id)');
            })
            ->whereIn('o.id', $orderIds)
            ->select([
                'o.id',
                'o.order_number',
                'o.unique_id as order_unique_id',
                DB::raw('DATE(o.order_date) as order_date'),
                'o.customer_name',
                DB::raw('o.subtotal - COALESCE(o.discount_amount, 0) as base_amount'),
                'o.tax_amount',
                'o.grand_total',
                'op.payment_method',
                'op.status as payment_status',
                DB::raw('COALESCE(op.payment_datetime, o.order_date) as payment_date'),
            ])
            ->get()
            ->map(function ($row) use ($productTypes) {
                $types = $productTypes[$row->id] ?? '';
                $typeList = array_values(array_filter(array_unique(explode(',', $types))));
                $source   = $this->classifyOrderRevenue($typeList);
                $pmLabel  = $this->mapOrderPaymentMethod($row->payment_method);
                $pmKey    = $row->payment_method ?? 'Other';

                return (object) [
                    'stream'             => 'order',
                    'payment_date'       => $row->payment_date,
                    'order_number'       => $row->order_number,
                    'order_unique_id'    => $row->order_unique_id,
                    'order_date'         => $row->order_date,
                    'customer_name'      => $row->customer_name ?: '—',
                    'revenue_source'     => $source['label'],
                    'revenue_source_key' => $source['key'],
                    'payment_method'     => $pmLabel,
                    'payment_method_key' => $pmKey,
                    'payment_status'     => $row->payment_status ?? 'Paid',
                    'base_amount'        => (float) ($row->base_amount ?? 0),
                    'tax_amount'         => (float) ($row->tax_amount ?? 0),
                    'grand_total'        => (float) ($row->grand_total ?? 0),
                    'included_because'   => $source['label'] . ' – Paid This Period',
                    'notes'              => null,
                ];
            });
    }

    // ─── Stream B: Refunds ────────────────────────────────────────────────────

    private function streamB(array $filters, string $startDate, string $endDate): Collection
    {
        $query = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')
            ->whereIn('op.status', ['Refunded', 'Partial Refund'])
            ->whereBetween(
                DB::raw('DATE(COALESCE(op.refunded_at, op.payment_datetime, op.created_at))'),
                [$startDate, $endDate]
            )
            ->select([
                'o.order_number',
                'o.unique_id as order_unique_id',
                DB::raw('DATE(o.order_date) as order_date'),
                'o.customer_name',
                'o.subtotal as order_subtotal',
                'o.tax_amount as order_tax_amount',
                'op.payment_method',
                'op.status as payment_status',
                'op.refund_amount',
                'op.tax_refunded',
                DB::raw('COALESCE(op.refunded_at, op.payment_datetime, op.created_at) as payment_date'),
            ]);

        if (!empty($filters['store'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('order_products as orp_rf')
                    ->whereColumn('orp_rf.order_id', 'o.id')
                    ->whereNull('orp_rf.deleted_at')
                    ->where(fn ($q) => $q
                        ->where('orp_rf.delivery_store_id', $filters['store'])
                        ->orWhere('orp_rf.pickup_store_id', $filters['store'])
                    );
            });
        }

        return $query->get()->map(function ($row) {
            $refundAmount = (float) ($row->refund_amount ?? 0);
            $isPartial    = $row->payment_status === 'Partial Refund';
            $pmLabel      = $this->mapOrderPaymentMethod($row->payment_method);

            // Same split SalesTaxReportEngine::refundRows() uses: trust the
            // stored tax_refunded when present (this is what makes a
            // Sales-Tax-Only refund reduce tax only, not revenue); fall
            // back to the proportional estimate only for legacy rows that
            // predate the tax_refunded column. Previously this stream
            // ignored tax_refunded entirely and folded the whole refund
            // into base_amount (revenue) — that broke reconciliation
            // against the Sales Tax Report the moment a refund's tax
            // portion didn't match its proportional share.
            $refundTax = ((float) ($row->tax_refunded ?? 0) > 0)
                ? (float) $row->tax_refunded
                : CustomHelper::calculateRefundSalesTax($refundAmount, (float) $row->order_subtotal, (float) $row->order_tax_amount);

            return (object) [
                'stream'             => 'refund',
                'payment_date'       => $row->payment_date,
                'order_number'       => $row->order_number,
                'order_unique_id'    => $row->order_unique_id,
                'order_date'         => $row->order_date,
                'customer_name'      => $row->customer_name ?: '—',
                'revenue_source'     => $isPartial ? 'Partial Refund' : 'Refund',
                'revenue_source_key' => 'refund',
                'payment_method'     => $pmLabel,
                'payment_method_key' => $row->payment_method ?? 'Other',
                'payment_status'     => $row->payment_status,
                'base_amount'        => -round($refundAmount - $refundTax, 2),
                'tax_amount'         => -$refundTax,
                'grand_total'        => -$refundAmount,
                'included_because'   => 'Refund Processed This Period',
                'notes'              => null,
            ];
        });
    }

    // ─── Stream C: Customer Account Payments ──────────────────────────────────

    private function streamC(array $filters, string $startDate, string $endDate): Collection
    {
        $query = DB::table('customer_accounts as ca')
            ->leftJoin('customers as c', 'c.id', '=', 'ca.customer_id')
            ->where('ca.type', 'payment')
            ->whereNull('ca.deleted_at')
            ->whereBetween('ca.date', [$startDate, $endDate])
            ->select([
                'ca.date as payment_date',
                'ca.amount',
                'ca.sales_tax',
                'ca.payment_type',
                DB::raw("CONCAT_WS(' ', c.first_name, c.last_name) as customer_name"),
                'c.unique_id as customer_unique_id',
            ]);

        // Mirror SalesReportEngineV2::queryAccountPayments() store filter behavior
        if (!empty($filters['store'])) {
            $customerIds = DB::table('orders')
                ->join('order_products', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.deleted_at')
                ->whereNull('order_products.deleted_at')
                ->where(fn ($q) => $q
                    ->where('order_products.delivery_store_id', $filters['store'])
                    ->orWhere('order_products.pickup_store_id', $filters['store'])
                )
                ->pluck('orders.customer_id')
                ->unique();

            $query->whereIn('ca.customer_id', $customerIds);
        }

        return $query->get()->map(function ($row) {
            $amount    = (float) ($row->amount ?? 0);
            $taxRate   = (float) ($row->sales_tax ?? 0);
            // Formula sourced from TaxCalculationService — see Financial Engine Phase 2.2.
            $breakdown = TaxCalculationService::extractTaxFromInclusiveAmount($amount, $taxRate);
            $pmLabel   = $this->mapAccountPaymentMethod($row->payment_type);
            $pmKey     = $this->mapAccountPaymentKey($row->payment_type);

            return (object) [
                'stream'             => 'account',
                'payment_date'       => $row->payment_date,
                'order_number'       => '—',
                'order_unique_id'    => null,
                'order_date'         => null,
                'customer_name'      => trim($row->customer_name) ?: '—',
                'revenue_source'     => 'Customer Account Payment',
                'revenue_source_key' => 'account_payment',
                'payment_method'     => $pmLabel,
                'payment_method_key' => $pmKey,
                'payment_status'     => 'Paid',
                'base_amount'        => $breakdown->baseAmount,
                'tax_amount'         => $breakdown->taxAmount,
                'grand_total'        => $amount,
                'included_because'   => 'Customer Account Payment Received',
                'notes'              => null,
            ];
        });
    }

    // ─── Stream D: Billing Engine Charges ─────────────────────────────────────

    private function streamD(array $filters, string $startDate, string $endDate): Collection
    {
        $query = DB::table('billing_charges as bc')
            ->leftJoin('customers as c', 'c.id', '=', 'bc.customer_id')
            ->leftJoin('orders as o', 'o.id', '=', 'bc.parent_order_id')
            ->where('bc.status', 'paid')
            ->whereNull('bc.customer_account_id')
            ->whereNull('bc.deleted_at')
            ->whereBetween(DB::raw('DATE(bc.paid_at)'), [$startDate, $endDate]);

        if (!empty($filters['store'])) {
            $storeId = (int) $filters['store'];
            $query->where(function ($q) use ($storeId) {
                $q->where('bc.store_id', $storeId)
                  ->orWhere(function ($q2) use ($storeId) {
                      $q2->whereNull('bc.store_id')
                         ->whereExists(function ($sub) use ($storeId) {
                             $sub->selectRaw('1')
                                 ->from('order_products as op_bc')
                                 ->whereColumn('op_bc.order_id', 'bc.parent_order_id')
                                 ->whereNull('op_bc.deleted_at')
                                 ->where(fn ($s) => $s
                                     ->where('op_bc.delivery_store_id', $storeId)
                                     ->orWhere('op_bc.pickup_store_id', $storeId)
                                 );
                         });
                  });
            });
        }

        return $query->select([
            'bc.billing_charge_type',
            'bc.amount',
            'bc.tax_amount',
            'bc.paid_at as payment_date',
            'o.order_number',
            'o.unique_id as order_unique_id',
            DB::raw('DATE(o.order_date) as order_date'),
            DB::raw("CONCAT_WS(' ', c.first_name, c.last_name) as customer_name"),
        ])->get()->map(function ($row) {
            $source    = $this->mapBillingChargeType($row->billing_charge_type);
            $amount    = (float) ($row->amount ?? 0);
            $taxAmount = (float) ($row->tax_amount ?? 0);

            return (object) [
                'stream'             => 'billing',
                'payment_date'       => $row->payment_date,
                'order_number'       => $row->order_number ?? '—',
                'order_unique_id'    => $row->order_unique_id,
                'order_date'         => $row->order_date,
                'customer_name'      => trim($row->customer_name) ?: '—',
                'revenue_source'     => $source['label'],
                'revenue_source_key' => $source['key'],
                'payment_method'     => 'Billing Engine',
                'payment_method_key' => 'billing_engine',
                'payment_status'     => 'Paid',
                'base_amount'        => $amount,
                'tax_amount'         => $taxAmount,
                'grand_total'        => $amount + $taxAmount,
                'included_because'   => $source['label'] . ' – Paid This Period',
                'notes'              => null,
            ];
        });
    }

    // ─── Classification Helpers ───────────────────────────────────────────────

    private function classifyOrderRevenue(array $types): array
    {
        if (empty($types)) {
            return ['key' => 'standard_order', 'label' => 'Standard Order'];
        }
        if (count($types) === 1) {
            return match ($types[0]) {
                'Rental'  => ['key' => 'standard_rental', 'label' => 'Standard Rental'],
                'Retail'  => ['key' => 'retail_sale',     'label' => 'Retail Sale'],
                'Service' => ['key' => 'service',         'label' => 'Service'],
                'Fee'     => ['key' => 'fee',             'label' => 'Fee'],
                default   => ['key' => 'standard_order',  'label' => 'Standard Order'],
            };
        }
        return ['key' => 'mixed_order', 'label' => 'Mixed Order'];
    }

    private function mapBillingChargeType(string $type): array
    {
        return match ($type) {
            'extension'      => ['key' => 'extension_billing', 'label' => 'Extension Billing'],
            'fuel'           => ['key' => 'fuel_charge',       'label' => 'Fuel Charge'],
            'damage'         => ['key' => 'damage_charge',     'label' => 'Damage Charge'],
            'cleaning'       => ['key' => 'cleaning_charge',   'label' => 'Cleaning Charge'],
            'delivery'       => ['key' => 'delivery_charge',   'label' => 'Delivery Charge'],
            'service_ticket' => ['key' => 'service_ticket',    'label' => 'Service Ticket'],
            'misc'           => ['key' => 'misc_charge',       'label' => 'Miscellaneous Charge'],
            default          => ['key' => 'misc_charge',       'label' => ucfirst($type ?? 'Charge')],
        };
    }

    private function mapOrderPaymentMethod(?string $method): string
    {
        return match ($method) {
            'Card'    => 'Credit / Debit Card',
            'Cash'    => 'Cash',
            'COD'     => 'Pay on Delivery (COD)',
            'Account' => 'Account',
            'Online'  => 'Direct Bank Transfer (ACH)',
            'Cheque'  => 'Check',
            'Other'   => 'Other',
            default   => $method ?? 'Other',
        };
    }

    private function mapAccountPaymentMethod(?string $type): string
    {
        return match ($type) {
            'CreditCard'   => 'Credit / Debit Card',
            'Cash'         => 'Cash',
            'Cheque'       => 'Check',
            'BankTransfer' => 'Direct Bank Transfer (ACH)',
            'Other'        => 'Other',
            default        => $type ?? 'Other',
        };
    }

    // Maps Customers\PaymentMethod keys to the same key space as OrderPaymentMethod
    // so account payments group with matching order payments in the Payment Method view.
    private function mapAccountPaymentKey(?string $type): string
    {
        return match ($type) {
            'CreditCard'   => 'Card',
            'Cash'         => 'Cash',
            'Cheque'       => 'Cheque',
            'BankTransfer' => 'Online',
            default        => 'Other',
        };
    }

    // ─── Static Lookup Tables (for view dropdowns) ────────────────────────────

    public static function revenueSourceOptions(): array
    {
        return [
            'standard_rental'  => 'Standard Rental',
            'retail_sale'      => 'Retail Sale',
            'service'          => 'Service',
            'fee'              => 'Fee',
            'mixed_order'      => 'Mixed Order',
            'standard_order'   => 'Standard Order',
            'extension_billing'=> 'Extension Billing',
            'fuel_charge'      => 'Fuel Charge',
            'damage_charge'    => 'Damage Charge',
            'cleaning_charge'  => 'Cleaning Charge',
            'delivery_charge'  => 'Delivery Charge',
            'service_ticket'   => 'Service Ticket',
            'misc_charge'      => 'Miscellaneous Charge',
            'account_payment'  => 'Customer Account Payment',
            'refund'           => 'Refund',
        ];
    }

    public static function paymentMethodOptions(): array
    {
        return [
            'Card'          => 'Credit / Debit Card',
            'Cash'          => 'Cash',
            'Cheque'        => 'Check',
            'COD'           => 'Pay on Delivery (COD)',
            'Other'         => 'Other',
            'billing_engine'=> 'Billing Engine',
        ];
    }

    // Revenue source sort order for the grouped view
    public static function revenueSourceOrder(): array
    {
        return [
            'standard_rental'   => 1,
            'retail_sale'       => 2,
            'service'           => 3,
            'fee'               => 4,
            'mixed_order'       => 5,
            'standard_order'    => 6,
            'extension_billing' => 7,
            'fuel_charge'       => 8,
            'damage_charge'     => 9,
            'cleaning_charge'   => 10,
            'delivery_charge'   => 11,
            'service_ticket'    => 12,
            'misc_charge'       => 13,
            'account_payment'   => 14,
            'refund'            => 15,
        ];
    }

    // Payment method sort order for the grouped view
    public static function paymentMethodOrder(): array
    {
        return [
            'Card'           => 1,
            'Cash'           => 2,
            'Cheque'         => 3,
            'COD'            => 4,
            'Other'          => 5,
            'billing_engine' => 6,
        ];
    }
}
