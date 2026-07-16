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

    /**
     * Phase 3D fix: this used to join each order to only its single
     * highest-id order_payments row (via a MAX(id) subquery with no status
     * filter — which could even resolve to a REFUND row on an order
     * refunded since) and attribute the WHOLE order's grand_total to it.
     * On any split-payment order that silently misattributed every other
     * payment method's actual collected amount to whichever payment
     * happened to be entered last. Now one ledger row is emitted per
     * actual SETTLED payment event (OrderPayment::scopeSettled()'s status
     * set — Paid, Partial Payment, or a legacy Invoice* row), using that
     * row's own `amount` — never the order's grand_total. This is also
     * why an order with no settled payment row yet (a POD/Account
     * placeholder order with nothing actually collected) now correctly
     * contributes zero rows here rather than fabricating one — this
     * stream's own docblock guarantee ("sums to the actual total
     * collected") only holds if uncollected orders emit nothing.
     *
     * order_payments has no per-row tax column, so each row's tax portion
     * is the order's tax_amount split proportionally to that row's share
     * of the order's total collected across its own settled rows — the
     * same proportional-split convention (deterministic remainder-to-
     * last-row rounding) PaymentAllocationService::calculateAllocationSplits()
     * already uses for the Standard refund calc type, applied here to the
     * collection side instead of the refund side.
     */
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

        $settledStatuses = collect(\App\Enums\Orders\OrderPaymentStatus::cases())
            ->filter(fn ($s) => $s->isSettled() || $s === \App\Enums\Orders\OrderPaymentStatus::PartialPayment)
            ->map(fn ($s) => $s->value)
            ->all();

        $paymentRows = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereIn('o.id', $orderIds)
            ->whereIn('op.status', $settledStatuses)
            ->select([
                'o.id as order_id',
                'o.order_number',
                'o.unique_id as order_unique_id',
                DB::raw('DATE(o.order_date) as order_date'),
                'o.customer_name',
                'o.tax_amount as order_tax_amount',
                'op.amount',
                'op.payment_method',
                'op.status as payment_status',
                DB::raw('COALESCE(op.payment_datetime, o.order_date) as payment_date'),
            ])
            ->orderBy('op.id')
            ->get();

        if ($paymentRows->isEmpty()) {
            return collect();
        }

        $rowsByOrder = $paymentRows->groupBy('order_id');

        return $rowsByOrder->flatMap(function ($rows, $orderId) use ($productTypes) {
            $types = $productTypes[$orderId] ?? '';
            $typeList = array_values(array_filter(array_unique(explode(',', $types))));
            $source = $this->classifyOrderRevenue($typeList);

            $orderTaxAmount = (float) ($rows->first()->order_tax_amount ?? 0);
            $totalCents = (int) round($rows->sum(fn ($r) => (float) $r->amount) * 100);
            $orderTaxCents = (int) round($orderTaxAmount * 100);
            $runningTaxCents = 0;
            $count = $rows->count();

            return $rows->values()->map(function ($row, $i) use ($source, $totalCents, $orderTaxCents, &$runningTaxCents, $count) {
                $amount = (float) $row->amount;
                $amountCents = (int) round($amount * 100);

                if ($i === $count - 1) {
                    $taxCents = $orderTaxCents - $runningTaxCents;
                } else {
                    $taxCents = $totalCents > 0 ? (int) round($orderTaxCents * $amountCents / $totalCents) : 0;
                    $runningTaxCents += $taxCents;
                }

                $tax = round($taxCents / 100, 2);
                $base = round($amount - $tax, 2);

                // Legacy Invoice* rows fuse status with method and may have
                // a null payment_method column — recover it from the
                // status the same way PaymentDescriptionPresenter::describe()
                // does, rather than mislabeling these as "Other."
                $method = $row->payment_method;
                if (!$method) {
                    $status = \App\Enums\Orders\OrderPaymentStatus::tryFrom($row->payment_status);
                    $method = $status?->impliedMethod()?->value;
                }

                return (object) [
                    'stream'             => 'order',
                    'payment_date'       => $row->payment_date,
                    'order_number'       => $row->order_number,
                    'order_unique_id'    => $row->order_unique_id,
                    'order_date'         => $row->order_date,
                    'customer_name'      => $row->customer_name ?: '—',
                    'revenue_source'     => $source['label'],
                    'revenue_source_key' => $source['key'],
                    'payment_method'     => $this->mapOrderPaymentMethod($method),
                    'payment_method_key' => $method ?? 'Other',
                    'payment_status'     => $row->payment_status ?? 'Paid',
                    'base_amount'        => $base,
                    'tax_amount'         => $tax,
                    'grand_total'        => $amount,
                    'included_because'   => $source['label'] . ' – Paid This Period',
                    'notes'              => null,
                ];
            });
        })->values();
    }

    // ─── Stream B: Refunds ────────────────────────────────────────────────────

    /**
     * Phase 3D: a multi-source refund event is now split into one row per
     * successfully-Allocated allocation, keyed by the ALLOCATION's original
     * payment's method — not the refund row's own payment_method, which
     * describes how the refund was paid OUT (e.g. Store Credit), not which
     * original payment(s) it drew down. A refund row with no allocations
     * yet (legacy/ambiguous — see PaymentAllocationService::attributionState())
     * keeps the prior whole-row behavior, so nothing here regresses for
     * unbackfilled history.
     */
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
                'op.id as payment_id',
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
                'op.refund_calculation_type',
                'op.refund_operation_status',
                'op.processed_by_name',
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

        $refundRows = $query->get();

        if ($refundRows->isEmpty()) {
            return collect();
        }

        $allocationsByRefund = \App\Models\Orders\OrderPaymentRefundAllocation::whereIn('refund_order_payment_id', $refundRows->pluck('payment_id'))
            ->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated->value)
            ->with('originalPayment')
            ->get()
            ->groupBy('refund_order_payment_id');

        // Phase 3D — Refund Reporting (mission §8): failed allocations per
        // refund event, for the "unprocessed amount" / "failure reason"
        // fields every split line for that event carries alongside its own
        // per-source amount — reused rather than a separate report engine,
        // per the mission's own "avoid duplicate financial sources of
        // truth" guidance.
        $failedAllocationsByRefund = \App\Models\Orders\OrderPaymentRefundAllocation::whereIn('refund_order_payment_id', $refundRows->pluck('payment_id'))
            ->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Failed->value)
            ->get()
            ->groupBy('refund_order_payment_id');

        return $refundRows->flatMap(function ($row) use ($allocationsByRefund, $failedAllocationsByRefund) {
            $isPartial = $row->payment_status === 'Partial Refund';
            $allocations = $allocationsByRefund->get($row->payment_id, collect());
            $failed = $failedAllocationsByRefund->get($row->payment_id, collect());

            $eventMeta = [
                'refund_calculation_type' => $row->refund_calculation_type
                    ? (\App\Enums\Orders\RefundCalculationType::tryFrom($row->refund_calculation_type)?->label() ?? $row->refund_calculation_type)
                    : 'Standard',
                'refund_operation_status' => $row->refund_operation_status ?? 'completed',
                'employee'                => $row->processed_by_name,
                'requested_amount'        => (float) $row->refund_amount,
                'unprocessed_amount'      => round((float) $failed->sum(
                    fn ($a) => (float) $a->allocated_amount - (float) ($a->processing_fee_retained ?? 0)
                ), 2),
                'failure_reason'          => $failed->pluck('failure_reason')->filter()->unique()->implode('; ') ?: null,
            ];

            if ($allocations->isEmpty()) {
                $refundAmount = (float) ($row->refund_amount ?? 0);

                // Same split SalesTaxReportEngine::refundRows() uses: trust
                // the stored tax_refunded when present (this is what makes
                // a Sales-Tax-Only refund reduce tax only, not revenue);
                // fall back to the proportional estimate only for legacy
                // rows that predate the tax_refunded column.
                $refundTax = ((float) ($row->tax_refunded ?? 0) > 0)
                    ? (float) $row->tax_refunded
                    : CustomHelper::calculateRefundSalesTax($refundAmount, (float) $row->order_subtotal, (float) $row->order_tax_amount);

                return collect([(object) [
                    'stream'             => 'refund',
                    'payment_date'       => $row->payment_date,
                    'order_number'       => $row->order_number,
                    'order_unique_id'    => $row->order_unique_id,
                    'order_date'         => $row->order_date,
                    'customer_name'      => $row->customer_name ?: '—',
                    'revenue_source'     => $isPartial ? 'Partial Refund' : 'Refund',
                    'revenue_source_key' => 'refund',
                    'payment_method'     => $this->mapOrderPaymentMethod($row->payment_method),
                    'payment_method_key' => $row->payment_method ?? 'Other',
                    'payment_status'     => $row->payment_status,
                    'base_amount'        => -round($refundAmount - $refundTax, 2),
                    'tax_amount'         => -$refundTax,
                    'grand_total'        => -$refundAmount,
                    'included_because'   => 'Refund Processed This Period',
                    'notes'              => null,
                    ...$eventMeta,
                ]]);
            }

            return $allocations->map(function ($allocation) use ($row, $isPartial, $eventMeta) {
                $original = $allocation->originalPayment;
                $netAmount = round((float) $allocation->allocated_amount - (float) ($allocation->processing_fee_retained ?? 0), 2);
                $tax = (float) $allocation->allocated_tax_amount;
                $base = round($netAmount - $tax, 2);
                $method = $original?->payment_method?->value;

                return (object) [
                    'stream'             => 'refund',
                    'payment_date'       => $row->payment_date,
                    'order_number'       => $row->order_number,
                    'order_unique_id'    => $row->order_unique_id,
                    'order_date'         => $row->order_date,
                    'customer_name'      => $row->customer_name ?: '—',
                    'revenue_source'     => $isPartial ? 'Partial Refund' : 'Refund',
                    'revenue_source_key' => 'refund',
                    'payment_method'     => $this->mapOrderPaymentMethod($method),
                    'payment_method_key' => $method ?? 'Other',
                    'payment_status'     => $row->payment_status,
                    'base_amount'        => -$base,
                    'tax_amount'         => -$tax,
                    'grand_total'        => -$netAmount,
                    'included_because'   => 'Refund Processed This Period',
                    'notes'              => null,
                    ...$eventMeta,
                ];
            });
        })->values();
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
