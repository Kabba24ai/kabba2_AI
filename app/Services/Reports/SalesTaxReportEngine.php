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
     *
     * Payment Architecture Finalization: this used to gate inclusion and
     * attribute the WHOLE order's subtotal/tax/discount/grand_total to
     * Order::lastPayment — the single highest-id order_payments row, no
     * status filter. On a split-payment order that misattributed every
     * dollar to whichever payment method happened to be entered last; on a
     * partially-paid order (a Partial Payment row that isn't the order's
     * last row) it counted the FULL grand_total as collected even though
     * only part of it actually had been. Now emits one row per QUALIFYING
     * payment event — mirroring PaymentReconciliationLedger::streamA(),
     * whose settled-status set and proportional tax/discount split (cents-
     * based, remainder to the last row) this reuses — so an order with N
     * qualifying payments contributes N correctly-attributed rows instead
     * of one row misattributed to a single payment.
     */
    public function salesRows(array $filters): Collection
    {
        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        // Only orders that own rental/retail product lines — matching the
        // rest of the reporting architecture (SalesReportingService::baseQuery).
        // Extension child orders have NO order_products rows; their money is
        // already represented by the linked Billing Engine extension charge
        // (Stream D), so admitting them here counted every paid extension twice.
        $orderIdsQuery = Order::query()
            ->select('id')
            ->whereHas('products')
            ->whereBetween('order_date', [$start, $end]);

        if (!empty($filters['store'])) {
            $orderIdsQuery->whereHas('products', fn($sub) => $sub->where(
                fn($s) => $s->where('delivery_store_id', $filters['store'])
                             ->orWhere('pickup_store_id', $filters['store'])
            ));
        }

        $orderIds = $orderIdsQuery->pluck('id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        // Same settled-status set streamA uses: Paid, the legacy Invoice*
        // statuses, and Partial Payment — a real, partially-collected event.
        $settledStatuses = collect(\App\Enums\Orders\OrderPaymentStatus::cases())
            ->filter(fn ($s) => $s->isSettled() || $s === \App\Enums\Orders\OrderPaymentStatus::PartialPayment)
            ->map(fn ($s) => $s->value)
            ->all();

        $paymentQuery = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereIn('o.id', $orderIds)
            ->whereIn('op.status', $settledStatuses)
            // Account-method rows are Stream C's (accountRows) — never
            // realized here until the customer_accounts payment lands.
            ->where('op.payment_method', '!=', 'Account')
            // Unpaid-on-delivery COD was never actually collected; only a
            // COD row that itself reached Paid status qualifies.
            ->where(function ($q) {
                $q->where('op.payment_method', '!=', 'COD')
                  ->orWhere('op.status', 'Paid');
            })
            // Store Credit is a discount, not a tender — a legacy StoreCredit
            // payment row must not attribute taxable sales.
            ->where('op.payment_method', '!=', 'StoreCredit');

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'All Methods') {
            $paymentQuery->where('op.payment_method', $filters['payment_method']);
        }

        $paymentRows = $paymentQuery
            ->select([
                'o.id as order_id',
                'o.unique_id as order_unique_id',
                DB::raw('DATE(o.order_date) as order_date'),
                'o.subtotal as order_subtotal',
                'o.tax_amount as order_tax_amount',
                'o.discount_amount as order_discount_amount',
                'op.amount',
                'op.payment_method',
                'op.status as payment_status',
            ])
            ->orderBy('op.id')
            ->get();

        if ($paymentRows->isEmpty()) {
            return collect();
        }

        $orders = Order::query()
            ->whereIn('id', $paymentRows->pluck('order_id')->unique())
            ->with([
                'shippingAddress:id,order_id,first_name,last_name',
                'products:id,order_id,product_name',
            ])
            ->get()
            ->keyBy('id');

        return $paymentRows->groupBy('order_id')->flatMap(function ($rows, $orderId) use ($orders) {
            $order = $orders[$orderId] ?? null;
            if (!$order) {
                return collect();
            }

            $orderTaxCents = (int) round((float) ($rows->first()->order_tax_amount ?? 0) * 100);
            $orderDiscountCents = (int) round((float) ($rows->first()->order_discount_amount ?? 0) * 100);
            $totalCents = (int) round($rows->sum(fn ($r) => (float) $r->amount) * 100);
            $runningTaxCents = 0;
            $runningDiscountCents = 0;
            $count = $rows->count();

            $customerName = $order->shippingAddress?->full_name ?? '-';
            $productsLabel = $order->products->pluck('product_name')->implode(', ');

            return $rows->values()->map(function ($row, $i) use (
                $order, $customerName, $productsLabel, $totalCents,
                $orderTaxCents, $orderDiscountCents, &$runningTaxCents, &$runningDiscountCents, $count
            ) {
                $amount = (float) $row->amount;
                $amountCents = (int) round($amount * 100);
                $isLast = $i === $count - 1;

                if ($isLast) {
                    $taxCents = $orderTaxCents - $runningTaxCents;
                    $discountCents = $orderDiscountCents - $runningDiscountCents;
                } else {
                    $taxCents = $totalCents > 0 ? (int) round($orderTaxCents * $amountCents / $totalCents) : 0;
                    $discountCents = $totalCents > 0 ? (int) round($orderDiscountCents * $amountCents / $totalCents) : 0;
                    $runningTaxCents += $taxCents;
                    $runningDiscountCents += $discountCents;
                }

                $tax = round($taxCents / 100, 2);
                $discount = round($discountCents / 100, 2);
                // grand_total = subtotal + tax_amount - discount_amount (discount applied post-tax)
                $subtotal = round($amount - $tax + $discount, 2);

                $method = $row->payment_method;
                if (!$method) {
                    $status = \App\Enums\Orders\OrderPaymentStatus::tryFrom($row->payment_status);
                    $method = $status?->impliedMethod()?->value;
                }

                return (object) [
                    'type'            => 'order',
                    'unique_id'       => $order->unique_id,
                    'link'            => $order->view_link,
                    'date'            => $row->order_date,
                    'customer_name'   => $customerName,
                    'products'        => $productsLabel,
                    'payment_type'    => \App\Enums\Orders\OrderPaymentMethod::tryFrom($method ?? '')?->label() ?? '-',
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $tax,
                    'discount_amount' => $discount,
                    'grand_total'     => $amount,
                ];
            });
        })->values();
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
                'refundAllocations' => fn ($q) => $q->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated->value)
                    ->with('originalPayment'),
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
            $storeId = (int) $filters['store'];
            $query->where(function ($outer) use ($storeId) {
                $outer->whereHas('order.products', fn($sub) => $sub->where(
                    fn($s) => $s->where('delivery_store_id', $storeId)
                                 ->orWhere('pickup_store_id', $storeId)
                ))
                // Extension-child refunds: the child owns no product rows, so
                // match through the linked charge's store — the same attribution
                // Stream D uses for the positive side (bc.store_id, falling back
                // to the parent order's product stores when store_id is null).
                ->orWhereHas('order.extensionCharge', function ($c) use ($storeId) {
                    $c->where(function ($q) use ($storeId) {
                        $q->where('store_id', $storeId)
                          ->orWhere(function ($q2) use ($storeId) {
                              $q2->whereNull('store_id')
                                 ->whereExists(function ($sub) use ($storeId) {
                                     $sub->selectRaw('1')
                                         ->from('order_products as op_bc')
                                         ->whereColumn('op_bc.order_id', 'billing_charges.parent_order_id')
                                         ->whereNull('op_bc.deleted_at')
                                         ->where(function ($s) use ($storeId) {
                                             $s->where('op_bc.delivery_store_id', $storeId)
                                               ->orWhere('op_bc.pickup_store_id', $storeId);
                                         });
                                 });
                          });
                    });
                });
            });
        }

        return $query->get()
            ->flatMap(function ($payment) {
                $order = $payment->order;

                if (!$order) {
                    return collect();
                }

                $refundDate = $payment->refunded_at
                    ?? $payment->payment_datetime
                    ?? $payment->created_at;
                $productsLabel = 'Refund — ' . $order->products->pluck('product_name')->implode(', ');

                // Phase 3D: kept in sync with PaymentReconciliationLedger::streamB() —
                // a multi-source refund with successfully-Allocated allocations
                // splits into one row per allocation, keyed by that
                // allocation's original payment's method, so the Sales Tax
                // Report and the Reconciliation Ledger never disagree about
                // which method a given dollar of refunded tax came from.
                $allocations = $payment->refundAllocations;

                if ($allocations->isEmpty()) {
                    $refundAmount = (float) $payment->refund_amount;

                    $refundTax = ((float) ($payment->tax_refunded ?? 0) > 0)
                        ? (float) $payment->tax_refunded
                        : CustomHelper::calculateRefundSalesTax(
                            $refundAmount,
                            $order->subtotal,
                            $order->tax_amount
                        );

                    return collect([(object) [
                        'type'            => 'refund',
                        'unique_id'       => $order->unique_id,
                        'link'            => $order->view_link,
                        'date'            => $refundDate,
                        'customer_name'   => $order->shippingAddress?->full_name ?? '-',
                        'products'        => $productsLabel,
                        'payment_type'    => $payment->payment_method?->label() ?? '-',
                        'subtotal'        => -(round($refundAmount - $refundTax, 2)),
                        'tax_amount'      => -$refundTax,
                        'discount_amount' => 0.0,
                        'grand_total'     => -$refundAmount,
                    ]]);
                }

                return $allocations->map(function ($allocation) use ($order, $refundDate, $productsLabel) {
                    $netAmount = round((float) $allocation->allocated_amount - (float) ($allocation->processing_fee_retained ?? 0), 2);
                    $tax = (float) $allocation->allocated_tax_amount;

                    return (object) [
                        'type'            => 'refund',
                        'unique_id'       => $order->unique_id,
                        'link'            => $order->view_link,
                        'date'            => $refundDate,
                        'customer_name'   => $order->shippingAddress?->full_name ?? '-',
                        'products'        => $productsLabel,
                        'payment_type'    => \App\Services\PaymentDescriptionPresenter::methodLabel($allocation->originalPayment?->payment_method),
                        'subtotal'        => -(round($netAmount - $tax, 2)),
                        'tax_amount'      => -$tax,
                        'discount_amount' => 0.0,
                        'grand_total'     => -$netAmount,
                    ];
                });
            })
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
            // Store Credit is a discount, not a tender — exclude legacy StoreCredit
            // A/R payments from taxable-sales attribution.
            ->where('payment_type', '!=', 'StoreCredit')
            ->whereBetween('date', [$start, $end]);

        // customer_accounts.payment_type uses Customers\PaymentMethod values (CreditCard, BankTransfer, …)
        // while the filter sends Orders\OrderPaymentMethod values (Card, Online, …).
        // 'Account' means "show account payment rows" — customer_accounts.type = 'payment' already
        // scopes to that subset, so no additional payment_type constraint is needed.
        $accountMethodMap = [
            'Card'   => 'CreditCard',
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
                'products'        => $this->caPaymentProductsLabel($payment->reason),
                'payment_type'    => $payment->payment_type?->label() ?? '-',
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => 0.0,
                'grand_total'     => $amount,
            ];
        });
    }

    /**
     * Sales Tax Architecture Correction — presentation only, no linking
     * inferred. A payment row's own `reason` column is set deterministically
     * at creation time from the originating charge's own reason (see
     * PaymentStoreController::handleCrmPayment(): "Payment — {$chargeAccount->reason}")
     * — this is NOT a guess based on timing/amount/customer, it is the
     * exact same string the charge itself was created with. Labeling this
     * row "Fuel Charge — Base" / "Damage Charge — Base" (matching Stream
     * E's "… — Sales Tax" rows for the same charge type) makes clear to
     * report readers that a base-only row and a tax-only row for the same
     * charge category are two halves of one transaction, not two separate
     * ones — without claiming a specific-row link that does not exist.
     */
    private function caPaymentProductsLabel(?string $reason): string
    {
        if ($reason && str_contains($reason, 'Fuel Charge')) {
            return 'Fuel Charge — Base';
        }
        if ($reason && str_contains($reason, 'Damages')) {
            return 'Damage Charge — Base';
        }

        return 'Payment Account';
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
     * are excluded HERE (their base amount is already represented by Stream C's
     * matching customer_accounts payment row) but their TAX is surfaced separately
     * by caTaxOnlyRows() below — see that method's docblock for why.
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
            // Gateway voids UPDATE the child's Paid payment row to 'Voided' (the
            // money never settled) but leave the charge status 'paid' — count an
            // extension only while its child order still holds an active Paid
            // payment. Refunds keep the original Paid row (a separate Refunded
            // row is added), so refunded extensions stay here and Stream B's
            // negative row nets them out on the refund date.
            ->where(function ($q) {
                $q->where('bc.billing_charge_type', '!=', 'extension')
                  ->orWhereExists(function ($sub) {
                      $sub->selectRaw('1')
                          ->from('order_payments as op_paid')
                          ->whereColumn('op_paid.order_id', 'bc.child_order_id')
                          ->whereNull('op_paid.deleted_at')
                          ->where('op_paid.status', 'Paid');
                  });
            })
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
                    'extension'      => 'Order Enhancement',
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

    /**
     * Reporting Timing Correction — this stream's ROLE, precisely:
     *
     *   Stream C (accountRows())     owns the original positive BASE/payment activity.
     *   Stream E (this method)       owns (a) the original positive TAX component
     *                                 of a Customer Account-linked Fuel/Damage
     *                                 charge, AND (b) authoritative negative
     *                                 base+tax adjustments for successful
     *                                 (status='allocated') Billing Charge Refund
     *                                 Allocations against such a charge.
     *
     * It is NOT "tax only" — do not describe it that way in new code/comments.
     * It carries negative BASE adjustments too, because the allocation record
     * (billing_charge_refunds) has the one durable, unambiguous relationship
     * to a specific Billing Charge that Stream C structurally lacks (see "WHY
     * THIS STREAM EXISTS" below) — so a refund's base correction has nowhere
     * else it CAN correctly live. This is still one method/one merged stream
     * in IndexController, not a new third stream.
     *
     * ── EVENT-BASED REPORTING (corrected — this method previously netted
     *    refunds retroactively into the ORIGINAL charge's row/date, which
     *    contradicted the canonical convention this codebase already uses
     *    everywhere else) ──────────────────────────────────────────────────
     * Confirmed by tracing refundRows() (Stream B) before making this
     * change: its own class-level docblock states outright "Stream B is
     * queried independently of Stream A. A June refund on a May order
     * appears in June's tax report regardless of which month the order was
     * created" — anchored on `COALESCE(refunded_at, payment_datetime,
     * created_at)`, the REFUND's own event date, never the original
     * transaction's date. That is the established canonical convention.
     * This method now follows it via TWO independently-dated queries:
     *
     *   1. originalTaxRows() — the charge's full, UNREDUCED tax_amount,
     *      dated bc.paid_at. Never permanently reduced by a later refund,
     *      exactly like Stream B leaves Stream A's original sale alone.
     *   2. refundAdjustmentRows() — one negative row per successful
     *      billing_charge_refunds allocation, dated by the REFUND's own
     *      event date (see date-field reasoning below), using the
     *      PERSISTED allocation base_amount/tax_amount/total_amount
     *      directly — never recomputed against today's tax rate.
     *
     * A charge paid in June with a refund allocated in July now correctly
     * shows the full June tax in a June-only report, the negative July
     * adjustment in a July-only report, and both (netting correctly) in a
     * report spanning both months. Verified by dedicated period tests.
     *
     * ── REFUND EVENT DATE ─────────────────────────────────────────────────
     * COALESCE(customer_accounts.date, billing_charge_refunds.created_at).
     * RefundStoreController's linked-refund path creates the CustomerAccount
     * refund row's `date` (explicitly `now()` at submission time) and the
     * billing_charge_refunds row in the SAME transaction, so in practice
     * they are identical to the second — `customer_accounts.date` is
     * preferred because it is the SAME column Stream C already treats as
     * the authoritative Customer-Account-side event date (accountRows() is
     * anchored on it), keeping the two streams' dating philosophy
     * consistent. The `created_at` fallback only matters if a
     * customer_account_id were ever missing, which the current write path
     * never allows for an Allocated row.
     *
     * ── WHY THIS STREAM EXISTS AT ALL ────────────────────────────────────
     * No persisted relationship connects a customer_accounts payment row to
     * the billing_charges row it settles — no FK column exists on either
     * side, and the candidate join keys (customer, date, amount, timing)
     * are not reliable: a customer can have two same-day open charges paid
     * separately, and CRM-originated charges have no order_product_id to
     * disambiguate through. This method deliberately does NOT attempt that
     * match for the ORIGINAL tax row. It reads billing_charges directly
     * instead, keyed only by the charge's own paid status/date. The refund
     * adjustment row is different: billing_charge_refunds DOES have an
     * authoritative, unambiguous billing_charge_id — no fuzzy matching
     * needed there.
     *
     * ── STORE ATTRIBUTION ─────────────────────────────────────────────────
     * Both the original-tax row and the refund-adjustment row filter by the
     * SAME rule: bc.store_id, falling back to the parent order's
     * order_products delivery/pickup store columns when store_id is null —
     * byte-for-byte the same fallback billingRows() (Stream D) already uses
     * for the positive extension/mobile side. A charge and its refund can
     * never land in inconsistent stores, because the refund row's store
     * filter is derived from ITS OWN billing_charge_id's bc.store_id, not
     * independently guessed. (Stream C itself still has no store data at
     * all on customer_accounts and continues to return empty under any
     * store filter — unchanged, unrelated to this correction.)
     *
     * ── THIS IS TRANSITIONAL, NOT THE TARGET ARCHITECTURE ────────────────
     * The durable fix is a real stored relationship between the Customer
     * Account transaction and the Billing Charge (e.g. a billing_charge_id
     * column on customer_accounts, set at payment-creation time in
     * PaymentStoreController::handleCrmPayment()) — NOT added in this
     * correction, per explicit instruction. Once that exists, Stream C
     * could report the correct tax itself and this method could be
     * retired entirely.
     */
    public function caLinkedTaxAndRefundRows(array $filters): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->resolveDateRange($filters);

        if (!$start || !$end) {
            return collect();
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'All Methods') {
            $skipMethods = ['COD', 'Cheque', 'Cash', 'Other', 'Online'];
            if (in_array($filters['payment_method'], $skipMethods)) {
                return collect();
            }
        }

        return $this->caOriginalTaxRows($filters, $start, $end)
            ->concat($this->caRefundAdjustmentRows($filters, $start, $end));
    }

    /** @deprecated Renamed to caLinkedTaxAndRefundRows() — no longer accurate, the stream also carries refund adjustments now. Kept only until every caller is confirmed updated. */
    public function caTaxOnlyRows(array $filters): \Illuminate\Support\Collection
    {
        return $this->caLinkedTaxAndRefundRows($filters);
    }

    private function billingChargeStoreFilter($query, int $storeId): void
    {
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

    /** The charge's own full, unreduced tax — dated bc.paid_at, never permanently reduced by a later refund. */
    private function caOriginalTaxRows(array $filters, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $query = DB::table('billing_charges as bc')
            ->leftJoin('customers as c', 'c.id', '=', 'bc.customer_id')
            ->leftJoin('orders as o', 'o.id', '=', 'bc.parent_order_id')
            ->where('bc.status', 'paid')
            ->whereNotNull('bc.customer_account_id')
            ->whereIn('bc.billing_charge_type', ['fuel', 'damage'])
            ->whereNull('bc.deleted_at')
            ->where('bc.tax_amount', '>', 0)
            ->whereBetween(DB::raw('DATE(bc.paid_at)'), [$start->toDateString(), $end->toDateString()]);

        if (!empty($filters['store'])) {
            $this->billingChargeStoreFilter($query, (int) $filters['store']);
        }

        return $query
            ->select(
                'bc.unique_id', 'bc.billing_charge_type', 'bc.tax_amount', 'bc.paid_at',
                'o.order_number', 'o.unique_id as order_unique_id',
                DB::raw("CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name")
            )
            ->get()
            ->map(function ($row) {
                $typeLabel = $row->billing_charge_type === 'fuel' ? 'Fuel Charge' : 'Damage Charge';
                $taxAmount = (float) ($row->tax_amount ?? 0);

                return (object) [
                    'type'            => 'billing_tax_only',
                    'unique_id'       => $row->unique_id ?? '-',
                    'link'            => $row->order_unique_id
                        ? '<a href="' . route('admin.order-management.orders.edit', $row->order_unique_id) . '" class="text-brand-500 underline font-bold">View Order</a>'
                        : '-',
                    'date'            => $row->paid_at,
                    'customer_name'   => $row->customer_name ?: '-',
                    'products'        => $typeLabel . ' — Sales Tax' . ($row->order_number ? ' (' . $row->order_number . ')' : ''),
                    'payment_type'    => 'Billing Engine',
                    'subtotal'        => 0.0,
                    'tax_amount'      => $taxAmount,
                    'discount_amount' => 0.0,
                    'grand_total'     => $taxAmount,
                ];
            });
    }

    /**
     * One negative row per successful (status='allocated') refund
     * allocation, dated by the refund's own event date — never the
     * originating charge's date. Uses the PERSISTED allocation amounts
     * directly (base_amount/tax_amount/total_amount), never recomputed
     * against today's tax rate.
     */
    private function caRefundAdjustmentRows(array $filters, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $query = DB::table('billing_charge_refunds as bcr')
            ->join('billing_charges as bc', 'bc.id', '=', 'bcr.billing_charge_id')
            ->leftJoin('customer_accounts as ca', 'ca.id', '=', 'bcr.customer_account_id')
            ->leftJoin('customers as c', 'c.id', '=', 'bc.customer_id')
            ->leftJoin('orders as o', 'o.id', '=', 'bc.parent_order_id')
            ->where('bcr.status', 'allocated')
            ->whereIn('bc.billing_charge_type', ['fuel', 'damage'])
            ->whereNull('bc.deleted_at')
            ->whereBetween(DB::raw('DATE(COALESCE(ca.date, bcr.created_at))'), [$start->toDateString(), $end->toDateString()]);

        if (!empty($filters['store'])) {
            $this->billingChargeStoreFilter($query, (int) $filters['store']);
        }

        return $query
            ->select(
                'bcr.id as allocation_id', 'bc.unique_id as charge_unique_id', 'bc.billing_charge_type',
                'bcr.base_amount', 'bcr.tax_amount', 'bcr.total_amount',
                DB::raw('COALESCE(ca.date, bcr.created_at) as event_date'),
                'o.order_number', 'o.unique_id as order_unique_id',
                DB::raw("CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name")
            )
            ->get()
            ->map(function ($row) {
                $typeLabel = $row->billing_charge_type === 'fuel' ? 'Fuel Charge' : 'Damage Charge';
                $base = (float) $row->base_amount;
                $tax  = (float) $row->tax_amount;

                // Matches the exact presentation vocabulary requested — a
                // base-only or tax-only refund (e.g. a Tax Free charge)
                // gets the precise label; the common case (both nonzero)
                // reads as one combined refund event, mirroring Stream B's
                // single-row-per-refund-event convention rather than
                // inventing a two-rows-per-event pattern with no precedent.
                $suffix = match (true) {
                    $tax <= 0.0 && $base > 0.0  => ' Refund — Base',
                    $base <= 0.0 && $tax > 0.0  => ' Refund — Sales Tax',
                    default                     => ' Refund — Base & Sales Tax',
                };

                return (object) [
                    'type'            => 'billing_refund_adjustment',
                    'unique_id'       => $row->charge_unique_id ?? '-',
                    'link'            => $row->order_unique_id
                        ? '<a href="' . route('admin.order-management.orders.edit', $row->order_unique_id) . '" class="text-brand-500 underline font-bold">View Order</a>'
                        : '-',
                    'date'            => $row->event_date,
                    'customer_name'   => $row->customer_name ?: '-',
                    'products'        => $typeLabel . $suffix
                        . ($row->order_number ? ' (' . $row->order_number . ')' : '')
                        . ' [Allocation #' . $row->allocation_id . ']',
                    'payment_type'    => 'Billing Engine',
                    'subtotal'        => -$base,
                    'tax_amount'      => -$tax,
                    'discount_amount' => 0.0,
                    'grand_total'     => -(round($base + $tax, 2)),
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
