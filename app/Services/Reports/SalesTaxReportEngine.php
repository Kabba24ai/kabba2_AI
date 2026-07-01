<?php

namespace App\Services\Reports;

use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SalesTaxReportEngine — three independent transaction streams for Sales Tax reporting.
 *
 * Transaction-date accounting:
 *   Stream A (salesRows)   → anchored on orders.order_date
 *   Stream B (refundRows)  → anchored on COALESCE(refunded_at, payment_datetime, created_at)
 *   Stream C (accountRows) → anchored on customer_accounts.date
 *
 * Stream B is queried independently of Stream A. A June refund on a May order
 * appears in June's tax report regardless of which month the order was created.
 */
class SalesTaxReportEngine
{
    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Stream A: taxable sales rows anchored on orders.order_date.
     */
    public function salesRows(array $filters): Collection
    {
        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        $query = Order::query()
            ->select(['id', 'unique_id', 'order_number', 'order_date', 'subtotal', 'tax_amount', 'discount_amount', 'grand_total'])
            ->with([
                'shippingAddress:id,order_id,first_name,last_name',
                'products:id,order_id,product_name',
                'lastPayment',
            ])
            ->whereHas('lastPayment', function ($q) {
                // Exclude Voided/Failed/Pending — tax was never actually collected on these.
                $q->whereNotIn('status', ['Voided', 'Failed', 'Pending'])
                  ->where(function ($inner) {
                      $inner->where('payment_method', '!=', 'COD')
                            ->orWhere(function ($q2) {
                                $q2->where('payment_method', 'COD')->where('status', 'Paid');
                            });
                  });
            })
            ->whereRelation('lastPayment', 'payment_method', '!=', 'Account')
            ->whereBetween('order_date', [$start, $end]);

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'All Methods') {
            $query->whereRelation('lastPayment', 'payment_method', $filters['payment_method']);
        }

        if (!empty($filters['store'])) {
            $query->whereHas('products', fn($sub) => $sub->where(
                fn($s) => $s->where('delivery_store_id', $filters['store'])
                             ->orWhere('pickup_store_id', $filters['store'])
            ));
        }

        return $query->get()->map(fn($order) => (object) [
            'type'            => 'order',
            'unique_id'       => $order->unique_id,
            'link'            => $order->view_link,
            'date'            => $order->order_date,
            'customer_name'   => $order->shippingAddress?->full_name ?? '-',
            'products'        => $order->products->pluck('product_name')->implode(', '),
            'payment_type'    => $order->last_payment_type?->label() ?? '-',
            'subtotal'        => (float) $order->subtotal,
            'tax_amount'      => (float) $order->tax_amount,
            'discount_amount' => (float) $order->discount_amount,
            'grand_total'     => (float) $order->grand_total,
        ]);
    }

    /**
     * Stream B: refund rows anchored on COALESCE(refunded_at, payment_datetime, created_at).
     *
     * This query is entirely independent of orders.order_date. A refund processed
     * in June on a May order appears only in June's tax report.
     *
     * orders is joined for display context (customer name, products, link) — not for filtering.
     */
    public function refundRows(array $filters): Collection
    {
        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        $query = OrderPayment::query()
            ->with([
                'order' => fn($q) => $q->with([
                    'shippingAddress:id,order_id,first_name,last_name',
                    'products:id,order_id,product_name',
                ]),
            ])
            ->whereIn('status', ['Refunded', 'Partial Refund'])
            ->whereNotNull('order_id')
            ->whereBetween(
                DB::raw('DATE(COALESCE(refunded_at, payment_datetime, created_at))'),
                [$start->toDateString(), $end->toDateString()]
            );

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'All Methods') {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['store'])) {
            $query->whereHas('order.products', fn($sub) => $sub->where(
                fn($s) => $s->where('delivery_store_id', $filters['store'])
                             ->orWhere('pickup_store_id', $filters['store'])
            ));
        }

        return $query->get()
            ->map(function ($payment) {
                $order = $payment->order;

                if (!$order) {
                    return null;
                }

                $refundAmount = (float) $payment->refund_amount;

                $refundTax = ((float) ($payment->tax_refunded ?? 0) > 0)
                    ? (float) $payment->tax_refunded
                    : CustomHelper::calculateRefundSalesTax(
                        $refundAmount,
                        $order->subtotal,
                        $order->tax_amount
                    );

                $refundDate = $payment->refunded_at
                    ?? $payment->payment_datetime
                    ?? $payment->created_at;

                return (object) [
                    'type'            => 'refund',
                    'unique_id'       => $order->unique_id,
                    'link'            => $order->view_link,
                    'date'            => $refundDate,
                    'customer_name'   => $order->shippingAddress?->full_name ?? '-',
                    'products'        => 'Refund — ' . $order->products->pluck('product_name')->implode(', '),
                    'payment_type'    => $payment->payment_method?->label() ?? '-',
                    'subtotal'        => -(round($refundAmount - $refundTax, 2)),
                    'tax_amount'      => -$refundTax,
                    'discount_amount' => 0.0,
                    'grand_total'     => -$refundAmount,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Stream C: account payment rows anchored on customer_accounts.date.
     * Excluded when a store filter is active (matches existing report behavior).
     */
    public function accountRows(array $filters): Collection
    {
        if (!empty($filters['store'])) {
            return collect();
        }

        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        $query = CustomerAccount::query()
            ->with('customer')
            ->where('type', 'payment')
            ->whereBetween('date', [$start, $end]);

        // customer_accounts.payment_type uses Customers\PaymentMethod values (CreditCard, BankTransfer, …)
        // while the filter sends Orders\OrderPaymentMethod values (Card, Online, …).
        // 'Account' means "show account payment rows" — customer_accounts.type = 'payment' already
        // scopes to that subset, so no additional payment_type constraint is needed.
        $accountMethodMap = [
            'Card'   => 'CreditCard',
            'Online' => 'BankTransfer',
            'Cash'   => 'Cash',
            'Cheque' => 'Cheque',
            'Other'  => 'Other',
        ];
        if (!empty($filters['payment_method'])) {
            if ($filters['payment_method'] === 'Account') {
                // Show all account rows — payment_type records which method settled the balance
                // (card, check, etc.) and is distinct from the Account stream concept itself.
            } elseif (isset($accountMethodMap[$filters['payment_method']])) {
                $query->where('payment_type', $accountMethodMap[$filters['payment_method']]);
            } else {
                // COD or unknown — no customer_account rows match these
                return collect();
            }
        }

        return $query->get()->map(function ($payment) {
            $amount    = (float) ($payment->amount ?? 0);
            $taxRate   = (float) ($payment->sales_tax ?? 0);
            $taxAmount = $taxRate > 0 ? $amount - $amount / (1 + $taxRate) : 0.0;
            $subtotal  = $amount - $taxAmount;

            return (object) [
                'type'            => 'payment',
                'unique_id'       => $payment->customer?->unique_id ?? '-',
                'link'            => '<a href="' . route('admin.reports.sales-tax.paymentview', $payment->customer?->unique_id) . '" class="text-brand-500 underline font-bold">View Payment</a>',
                'date'            => $payment->date,
                'customer_name'   => $payment->customer?->full_name ?? '-',
                'products'        => 'Payment Account',
                'payment_type'    => $payment->payment_type?->label() ?? '-',
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => 0.0,
                'grand_total'     => $amount,
            ];
        });
    }

    /**
     * Stream D: Billing Engine charge rows anchored on billing_charges.paid_at.
     *
     * Includes paid charges that have no customer_account_id — meaning they are NOT
     * already captured via Stream C (accountRows). Specifically:
     *   - Extension charges (type='extension')
     *   - Mobile-originated damage/fuel charges (source_module='mobile_checklist')
     *   - Any future billing_charges type that bypasses the customer_accounts system
     *
     * Fuel/damage charges routed through customer_accounts (Dashboard/Order-Edit paths)
     * are excluded by the customer_account_id IS NULL guard.
     */
    public function billingRows(array $filters): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        $query = DB::table('billing_charges as bc')
            ->leftJoin('customers as c', 'c.id', '=', 'bc.customer_id')
            ->leftJoin('orders as o', 'o.id', '=', 'bc.parent_order_id')
            ->where('bc.status', 'paid')
            ->whereNull('bc.customer_account_id')
            ->whereNull('bc.deleted_at')
            ->whereBetween(DB::raw('DATE(bc.paid_at)'), [$start->toDateString(), $end->toDateString()]);

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
                                 ->where(function ($s) use ($storeId) {
                                     $s->where('op_bc.delivery_store_id', $storeId)
                                       ->orWhere('op_bc.pickup_store_id', $storeId);
                                 });
                         });
                  });
            });
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'All Methods') {
            // Billing Engine charges are not method-filterable in Phase 1 — no payment_method
            // column on billing_charges. Skip non-matching method filters; include for 'Card'
            // and 'Account' since extensions are typically charged by card.
            // Return empty for COD / Cheque / Cash / Other filters (billing charges are not these).
            $skipMethods = ['COD', 'Cheque', 'Cash', 'Other', 'Online'];
            if (in_array($filters['payment_method'], $skipMethods)) {
                return collect();
            }
        }

        return $query
            ->select(
                'bc.unique_id',
                'bc.billing_charge_type',
                'bc.amount',
                'bc.tax_amount',
                'bc.paid_at',
                'o.order_number',
                'o.unique_id as order_unique_id',
                DB::raw("CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name")
            )
            ->get()
            ->map(function ($row) {
                $amount     = (float) ($row->amount     ?? 0);
                $taxAmount  = (float) ($row->tax_amount ?? 0);
                $grandTotal = $amount + $taxAmount;

                $typeLabel = match ($row->billing_charge_type) {
                    'extension'      => 'Rental Extension',
                    'fuel'           => 'Fuel Charge',
                    'damage'         => 'Damage Charge',
                    'cleaning'       => 'Cleaning Fee',
                    'delivery'       => 'Delivery Fee',
                    'misc'           => 'Miscellaneous',
                    'service_ticket' => 'Service Ticket',
                    default          => ucfirst($row->billing_charge_type ?? 'Charge'),
                };

                $link = $row->order_unique_id
                    ? '<a href="' . route('admin.order-management.orders.edit', $row->order_unique_id) . '" class="text-brand-500 underline font-bold">View Order</a>'
                    : '-';

                return (object) [
                    'type'            => 'billing',
                    'unique_id'       => $row->unique_id ?? '-',
                    'link'            => $link,
                    'date'            => $row->paid_at,
                    'customer_name'   => $row->customer_name ?: '-',
                    'products'        => $typeLabel . ($row->order_number ? ' — ' . $row->order_number : ''),
                    'payment_type'    => 'Billing Engine',
                    'subtotal'        => $amount,
                    'tax_amount'      => $taxAmount,
                    'discount_amount' => 0.0,
                    'grand_total'     => $grandTotal,
                ];
            });
    }

    // ─── Public Helper ────────────────────────────────────────────────────────

    /**
     * Resolve [$start, $end] Carbon instances from filter inputs.
     * Public so the controller can use it for the extra-charges query.
     */
    public function resolveDateRange(array $filters): array
    {
        if (!empty($filters['month_range'])) {
            [$year, $month] = explode('-', $filters['month_range']);
            return [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ];
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            return [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        // return [null, null];
         // Default = Current Month
    return [
        now()->startOfMonth(),
        now()->endOfMonth(),
    ];
    }
}
