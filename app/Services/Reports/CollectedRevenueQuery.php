<?php

namespace App\Services\Reports;

use App\Services\Reports\Support\ProportionalPaymentSplit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CollectedRevenueQuery — the single source of truth for "collected revenue
 * per payment event" (cash-basis, payment-date accounting).
 *
 * Consumed by the Sales Summary (SalesReportEngineV2), the Sales Tax Report
 * Stream A (SalesTaxReportEngine::salesRows), and the Payment Reconciliation
 * Ledger Stream A — and, through V2, the dashboard financial KPIs. None of
 * those surfaces reproduce the allocation formulas: they all read the rows
 * produced here, which allocate exclusively through ProportionalPaymentSplit.
 *
 * Semantics (locked with ownership 2026-07-31):
 *   - The payment's execution date (COALESCE(payment_datetime, created_at))
 *     controls the reporting period. orders.order_date never buckets money.
 *   - Unpaid orders contribute nothing.
 *   - Partial payments contribute only their proportional revenue/tax/discount
 *     (denominator = the order's canonical grand_total).
 *   - Later payments never restate earlier periods (temporal stability — see
 *     ProportionalPaymentSplit). To guarantee it, the allocation is computed
 *     over the order's FULL qualifying payment set (all periods) and only the
 *     in-window rows are returned.
 *   - The payment completing the order absorbs the final-cent remainder;
 *     cumulative allocations are capped at the order's stored figures.
 *   - Overpayment (amount beyond the order's grand_total) is surfaced on the
 *     row separately and never creates revenue or tax.
 *   - Gift-card redemption is the mirror image of overpayment: it creates
 *     REVENUE AND TAX but no cash, because the money arrived earlier, when the
 *     card was funded. Marked per row via `is_cash_tender` /
 *     `non_cash_tender_class`; the payment stays in the allocation so the
 *     revenue is recognised in full. See docs/gift-cards/REPORTING_TREATMENT.md.
 *
 * LINE ATTRIBUTION (canonical decomposition — filtered views build on this):
 *   Each row carries a `lines` array partitioning that payment's canonical
 *   figures across the order's product lines via
 *   ProportionalPaymentSplit::distribute() — base/discount/store-credit by
 *   line sub-total weight, tax by line tax weight (a non-taxable line never
 *   receives tax), components by their own line values. By construction:
 *     Σ lines.base == row.base, Σ lines.tax == row.tax, etc. (exact, in cents)
 *   so any filtered view (product / category / store / type) is an exact
 *   partition of the same canonical amounts: filtered totals roll up to the
 *   unfiltered totals, and filtering can never change what a payment is worth.
 *   No filtered report recalculates tax or discount — they sum these
 *   partitions. Each line carries `matches` (does it satisfy the active line
 *   filters) so consumers just sum matching lines.
 *
 * Qualifying payment = settled status (Paid / Invoice*) or Partial Payment,
 * excluding Account-method rows (their cash is the account-payments stream),
 * unpaid COD placeholders, and StoreCredit (a discount, not a tender); on a
 * non-deleted order that owns product lines (extension children have none —
 * their money is the billing-charges stream).
 */
class CollectedRevenueQuery
{
    /**
     * @param array  $filters   report filters (store, item_type, sale_type,
     *                          category, product, employee_id, payment_status)
     * @param string $startDate inclusive Y-m-d window start (payment dates)
     * @param string $endDate   inclusive Y-m-d window end
     */
    public function rows(array $filters, string $startDate, string $endDate): Collection
    {
        $settledStatuses = collect(\App\Enums\Orders\OrderPaymentStatus::cases())
            ->filter(fn ($s) => $s->isSettled() || $s === \App\Enums\Orders\OrderPaymentStatus::PartialPayment)
            ->map(fn ($s) => $s->value)
            ->all();

        // Orders whose money landed IN this window. Inclusion is driven purely
        // by when cash arrived — this is the cash-basis rule.
        $windowOrderIds = $this->qualifyingPayments($settledStatuses, $filters)
            ->whereBetween(DB::raw('DATE(COALESCE(op.payment_datetime, op.created_at))'), [$startDate, $endDate])
            ->distinct()
            ->pluck('o.id');

        if ($windowOrderIds->isEmpty()) {
            return collect();
        }

        // ALL qualifying payments of those orders (every period), in
        // chronological order — the allocation must see the full set so each
        // payment's share is period-stable. Only in-window rows are emitted.
        $payments = $this->qualifyingPayments($settledStatuses, $filters)
            ->whereIn('o.id', $windowOrderIds)
            ->select([
                'o.id as order_id',
                'o.unique_id as order_unique_id',
                'o.order_number',
                DB::raw('DATE(o.order_date) as order_date'),
                'o.customer_name',
                'o.subtotal as order_subtotal',
                'o.tax_amount as order_tax',
                'o.discount_amount as order_discount',
                'o.grand_total as order_grand_total',
                'op.id as payment_id',
                'op.amount',
                'op.payment_method',
                'op.status as payment_status',
                DB::raw('COALESCE(op.payment_datetime, op.created_at) as payment_datetime'),
                DB::raw('DATE(COALESCE(op.payment_datetime, op.created_at)) as payment_date'),
            ])
            ->orderByRaw('COALESCE(op.payment_datetime, op.created_at) ASC')
            ->orderBy('op.id')
            ->get();

        if ($payments->isEmpty()) {
            return collect();
        }

        $orderIds     = $payments->pluck('order_id')->unique()->values();
        $linesByOrder = $this->orderLines($orderIds);
        $scByOrder    = $this->storeCreditDiscounts($orderIds);
        $lineFilters  = $this->hasLineFilters($filters);

        // Which payment rows are gift-card redemptions, and what kind of value
        // funded them. Loaded once for the whole set — see giftCardRedemptions().
        $giftCardByPayment = $this->giftCardRedemptions($payments->pluck('payment_id'));

        $out = collect();

        foreach ($payments->groupBy('order_id') as $orderId => $rows) {
            $rows  = $rows->values();
            $lines = ($linesByOrder[$orderId] ?? collect())->values();

            // Mark each line against the active line filters (stable line order).
            $matchFlags = $lines->map(fn ($l) => $this->lineMatches($l, $filters))->all();

            // Line filters active and this order has no matching line — out of
            // scope entirely (mirrors the historical baseQuery join).
            if ($lineFilters && !in_array(true, $matchFlags, true)) {
                continue;
            }

            $first         = $rows->first();
            $orderTax      = (float) ($first->order_tax ?? 0);
            $orderDiscount = (float) ($first->order_discount ?? 0);
            $orderTotal    = (float) ($first->order_grand_total ?? 0);
            $amounts       = $rows->map(fn ($r) => (float) $r->amount)->all();

            $sc = $scByOrder[$orderId] ?? null;

            // Level 1 — canonical PAYMENT allocation. Every order-level figure
            // distributes through the SAME primitive.
            $applied   = ProportionalPaymentSplit::applied($orderTotal, $amounts);
            $taxAlloc  = ProportionalPaymentSplit::allocate($orderTax, $orderTotal, $amounts);
            $discAlloc = ProportionalPaymentSplit::allocate($orderDiscount, $orderTotal, $amounts);
            $scDisc    = ProportionalPaymentSplit::allocate((float) ($sc->disc ?? 0), $orderTotal, $amounts);
            $scDelta   = ProportionalPaymentSplit::allocate((float) ($sc->tax_delta ?? 0), $orderTotal, $amounts);
            $delivery  = ProportionalPaymentSplit::allocate((float) $lines->sum('delivery_value'), $orderTotal, $amounts);
            $dw        = ProportionalPaymentSplit::allocate((float) $lines->sum('dw_value'), $orderTotal, $amounts);
            $track     = ProportionalPaymentSplit::allocate((float) $lines->sum('track_value'), $orderTotal, $amounts);
            $tire      = ProportionalPaymentSplit::allocate((float) $lines->sum('tire_value'), $orderTotal, $amounts);

            // Level 2 — weight vectors for the line decomposition. Tax follows
            // line tax (a non-taxable line never receives tax; degenerate
            // zero-everywhere falls back to sub-total weights so additivity is
            // preserved without inventing taxable lines arbitrarily).
            $subWeights = $lines->map(fn ($l) => (float) $l->sub_total)->all();
            $taxWeights = $lines->map(fn ($l) => (float) $l->tax)->all();
            if (array_sum($taxWeights) <= 0) {
                $taxWeights = $subWeights;
            }

            foreach ($rows as $i => $row) {
                // Emit only the payments that actually landed in this window.
                if ($row->payment_date < $startDate || $row->payment_date > $endDate) {
                    continue;
                }

                $base = round($applied[$i]['applied'] - $taxAlloc[$i] + $discAlloc[$i], 2);

                // Level 2 — partition THIS payment's canonical figures across
                // the lines. Σ of each column equals the payment figure exactly.
                $lineBase  = ProportionalPaymentSplit::distribute($base, $subWeights);
                $lineTax   = ProportionalPaymentSplit::distribute($taxAlloc[$i], $taxWeights);
                $lineDisc  = ProportionalPaymentSplit::distribute($discAlloc[$i], $subWeights);
                $lineScD   = ProportionalPaymentSplit::distribute($scDisc[$i], $subWeights);
                $lineScT   = ProportionalPaymentSplit::distribute($scDelta[$i], $taxWeights);
                $lineDeliv = ProportionalPaymentSplit::distribute($delivery[$i], $lines->map(fn ($l) => (float) $l->delivery_value)->all());
                $lineDw    = ProportionalPaymentSplit::distribute($dw[$i], $lines->map(fn ($l) => (float) $l->dw_value)->all());
                $lineTrack = ProportionalPaymentSplit::distribute($track[$i], $lines->map(fn ($l) => (float) $l->track_value)->all());
                $lineTire  = ProportionalPaymentSplit::distribute($tire[$i], $lines->map(fn ($l) => (float) $l->tire_value)->all());

                $lineAllocations = $lines->map(fn ($l, $j) => (object) [
                    'line_id'           => (int) $l->id,
                    'product_id'        => $l->product_id !== null ? (int) $l->product_id : null,
                    'product_type'      => $l->product_type,
                    'category_id'       => $l->primary_category_id !== null ? (int) $l->primary_category_id : null,
                    'delivery_store_id' => $l->delivery_store_id !== null ? (int) $l->delivery_store_id : null,
                    'pickup_store_id'   => $l->pickup_store_id !== null ? (int) $l->pickup_store_id : null,
                    'matches'           => $matchFlags[$j],
                    'base'              => $lineBase[$j],
                    'tax'               => $lineTax[$j],
                    'discount'          => $lineDisc[$j],
                    'sc_discount'       => $lineScD[$j],
                    'sc_tax_delta'      => $lineScT[$j],
                    'delivery'          => $lineDeliv[$j],
                    'dw'                => $lineDw[$j],
                    'track'             => $lineTrack[$j],
                    'tire'              => $lineTire[$j],
                ])->values()->all();

                $out->push((object) [
                    'order_id'          => (int) $row->order_id,
                    'order_unique_id'   => $row->order_unique_id,
                    'order_number'      => $row->order_number,
                    'order_date'        => $row->order_date,
                    'customer_name'     => $row->customer_name,
                    'order_subtotal'    => (float) $row->order_subtotal,
                    'order_tax'         => $orderTax,
                    'order_discount'    => $orderDiscount,
                    'order_grand_total' => $orderTotal,
                    'payment_id'        => (int) $row->payment_id,
                    'payment_date'      => $row->payment_date,
                    'payment_datetime'  => $row->payment_datetime,
                    'payment_method'    => $row->payment_method,
                    'payment_status'    => $row->payment_status,
                    'amount'            => (float) $row->amount,

                    // ── Cash vs revenue, separated ────────────────────────
                    //
                    // Every other tender in this system moves money at the
                    // moment it is applied, so revenue and cash are the same
                    // figure. A gift card moved its money EARLIER — when the
                    // card was funded — so the revenue below is real and the
                    // cash is not.
                    //
                    // These two fields are the ONLY way that distinction is
                    // expressed. The payment deliberately remains in the
                    // qualifying set: removing it would delete the revenue and
                    // the sales tax along with the cash (a $550 order paid by
                    // card would allocate as a $0 order). Consumers that care
                    // about CASH filter on `is_cash_tender`; consumers that
                    // care about REVENUE ignore it and are correct by default.
                    'is_cash_tender'        => !isset($giftCardByPayment[$row->payment_id]),
                    'non_cash_tender_class' => $giftCardByPayment[$row->payment_id] ?? null,
                    'applied'           => $applied[$i]['applied'],
                    'overpayment'       => $applied[$i]['overpayment'],
                    'tax'               => $taxAlloc[$i],
                    'discount'          => $discAlloc[$i],
                    // Pre-discount collected revenue: lifetime Σ = order subtotal.
                    'base'              => $base,
                    'sc_discount'       => $scDisc[$i],
                    'sc_tax_delta'      => $scDelta[$i],
                    'delivery'          => $delivery[$i],
                    'dw'                => $dw[$i],
                    'track'             => $track[$i],
                    'tire'              => $tire[$i],
                    'lines'             => $lineAllocations,
                ]);
            }
        }

        return $out;
    }

    /** Whether any line-level scope filter (store / type / category / product) is active. */
    public function hasLineFilters(array $filters): bool
    {
        return !empty($filters['store'])
            || (!empty($filters['item_type']) && $filters['item_type'] !== 'all')
            || (!empty($filters['sale_type']) && $filters['sale_type'] !== 'all')
            || !empty($filters['category'])
            || !empty($filters['product']);
    }

    /**
     * Sum one line-allocation column over a row's MATCHING lines. The standard
     * accessor for filtered views — never recalculates, only sums partitions.
     */
    public static function matchedLineSum(object $row, string $field): float
    {
        $sum = 0.0;
        foreach ($row->lines as $line) {
            if ($line->matches) {
                $sum += $line->{$field};
            }
        }

        return round($sum, 2);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * Payment id => the class of gift-card value that funded it.
     *
     * `gift_card_purchased` — a customer prepaid; redeeming draws down a
     *                         LIABILITY the business owes.
     * `gift_card_granted`   — the business gave the value away; redeeming
     *                         consumes MERCHANT-FUNDED PROMOTIONAL VALUE.
     *
     * Both are non-cash at redemption, and both must be told apart: summing
     * them would report money owed and money given away as one obligation.
     *
     * Read from the LEDGER rather than from `order_payments.payment_method`.
     * The method says "GiftCard" but cannot say which kind of card, and a row
     * hand-entered before this feature existed carries no card at all — such a
     * row has no ledger link, so it is correctly treated as cash and legacy
     * reporting is left exactly as it was.
     *
     * Absent gracefully: if the gift-card tables have not been migrated yet
     * (a report run mid-deploy), every payment is cash and this reduces to
     * today's behaviour rather than failing the report.
     *
     * @return array<int,string>
     */
    private function giftCardRedemptions(Collection $paymentIds): array
    {
        if ($paymentIds->isEmpty() || !\Illuminate\Support\Facades\Schema::hasTable('gift_card_transactions')) {
            return [];
        }

        return DB::table('gift_card_transactions as gct')
            ->join('gift_cards as gc', 'gc.id', '=', 'gct.gift_card_id')
            ->whereIn('gct.order_payment_id', $paymentIds)
            ->where('gct.type', 'redemption')
            ->pluck('gc.issuance_class', 'gct.order_payment_id')
            ->map(fn ($class) => 'gift_card_'.$class)
            ->all();
    }

    /**
     * The qualifying-payments universe (no date predicate — callers window it).
     * Both passes (window scoping and full-set fetch) share this so they see an
     * identical payment universe.
     *
     * NOTE ON GIFT CARDS. `GiftCard` is deliberately NOT excluded here, and
     * must not be added to the `!=` list below. Store Credit is excluded
     * because it is a pre-tax DISCOUNT — the order's subtotal, tax and grand
     * total are already lower, so its payment row duplicates a reduction
     * already recorded. A gift card reduces nothing: the order is fully priced
     * and fully taxed, and the revenue is real. Excluding it would make
     * allocation see a $0 order and delete both the revenue and the sales tax
     * owed on it. The cash/revenue split is expressed on the emitted row
     * instead — see `is_cash_tender` above.
     */
    private function qualifyingPayments(array $settledStatuses, array $filters): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')
            ->whereIn('op.status', $settledStatuses)
            // Account rows are AR markers — real cash arrives via
            // customer_accounts (the account-payments stream).
            ->where('op.payment_method', '!=', 'Account')
            // Unpaid-on-delivery COD was never actually collected.
            ->where(function ($w) {
                $w->where('op.payment_method', '!=', 'COD')
                  ->orWhere('op.status', 'Paid');
            })
            // Store Credit is a discount, not a tender.
            ->where('op.payment_method', '!=', 'StoreCredit')
            // Orders that own product lines — extension children have none;
            // their money is already the billing-charges stream.
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('order_products')
                    ->whereColumn('order_products.order_id', 'o.id')
                    ->whereNull('order_products.deleted_at');
            });

        if (!empty($filters['store'])) {
            $q->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('order_products as ops')
                    ->whereColumn('ops.order_id', 'o.id')
                    ->whereNull('ops.deleted_at')
                    ->where(function ($s) use ($filters) {
                        $s->where('ops.delivery_store_id', $filters['store'])
                          ->orWhere('ops.pickup_store_id', $filters['store']);
                    });
            });
        }

        // Employee = order creator (same rule as SalesReportingService).
        if (!empty($filters['employee_id'])) {
            $q->where('o.created_by_id', $filters['employee_id'])
              ->where('o.created_by_type', \App\Models\Iam\Personnel\User::class);
        }

        // Account Collections view: cash actually received on orders sitting on
        // a customer account. (Under cash basis the 'paid' bucket needs no
        // extra scoping — the qualifying rows ARE the realized cash.)
        $paymentStatus = $filters['payment_status'] ?? 'paid';
        if ($paymentStatus === 'account') {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('order_payments as op_acct')
                    ->whereColumn('op_acct.order_id', 'o.id')
                    ->where('op_acct.payment_method', 'Account');
            });
        }

        // POD scope: cash actually collected on orders still awaiting COD.
        // (The Sales Summary's POD view itself uses the legacy order-date
        // expected-value path — this scoping only defines what the ledger's
        // POD lens shows: real deposits on outstanding-COD orders.)
        if ($paymentStatus === 'pod') {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('order_payments as op_pod')
                    ->whereColumn('op_pod.order_id', 'o.id')
                    ->where('op_pod.payment_method', 'COD')
                    ->where('op_pod.status', '!=', 'Paid');
            });
        }

        return $q;
    }

    /**
     * The orders' product lines in stable (id) order, with product type,
     * primary category, and the component values (same JSON extractions the
     * Sales Summary has always used). This is the attribution basis for the
     * line decomposition.
     */
    private function orderLines(Collection $orderIds): Collection
    {
        $deliverySql = "COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.product_data, '$.service_option_price')), '') AS DECIMAL(12,2)), 0)";
        $dwSql       = "COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.product_data, '$.product_rental_items_prices.rental_damage_waiver')), '') AS DECIMAL(12,2)), 0) * COALESCE(l.quantity, 1)";
        $trackSql    = "COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.product_data, '$.product_rental_items_prices.rental_track_insurance')), '') AS DECIMAL(12,2)), 0) * COALESCE(l.quantity, 1)";
        $tireSql     = "COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.product_data, '$.product_rental_items_prices.rental_tire_insurance')), '') AS DECIMAL(12,2)), 0) * COALESCE(l.quantity, 1)";

        return DB::table('order_products as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            // Primary (lowest-id) category per product — same convention as
            // SalesReportingService::baseQuery().
            ->leftJoinSub(
                DB::table('product_category_children')
                    ->select('product_id', DB::raw('MIN(product_category_id) as primary_category_id'))
                    ->groupBy('product_id'),
                'pcc',
                'pcc.product_id',
                '=',
                'l.product_id'
            )
            ->whereIn('l.order_id', $orderIds)
            ->whereNull('l.deleted_at')
            ->selectRaw("
                l.id, l.order_id, l.product_id, l.delivery_store_id, l.pickup_store_id,
                l.sub_total, l.tax,
                p.product_type,
                pcc.primary_category_id,
                {$deliverySql} AS delivery_value,
                {$dwSql}       AS dw_value,
                {$trackSql}    AS track_value,
                {$tireSql}     AS tire_value
            ")
            ->orderBy('l.id')
            ->get()
            ->groupBy('order_id');
    }

    /** Does this line satisfy the active line-level filters? */
    private function lineMatches(object $line, array $filters): bool
    {
        if (!empty($filters['store'])) {
            $store = (int) $filters['store'];
            if ((int) ($line->delivery_store_id ?? 0) !== $store && (int) ($line->pickup_store_id ?? 0) !== $store) {
                return false;
            }
        }

        $itemType = null;
        if (!empty($filters['item_type']) && $filters['item_type'] !== 'all') {
            $itemType = ucfirst($filters['item_type']);
        } elseif (!empty($filters['sale_type']) && $filters['sale_type'] !== 'all') {
            $itemType = ['rental' => 'Rental', 'retail' => 'Retail'][$filters['sale_type']] ?? null;
        }
        if ($itemType !== null && $line->product_type !== $itemType) {
            return false;
        }

        if (!empty($filters['category']) && (int) ($line->primary_category_id ?? 0) !== (int) $filters['category']) {
            return false;
        }

        if (!empty($filters['product']) && (int) ($line->product_id ?? 0) !== (int) $filters['product']) {
            return false;
        }

        return true;
    }

    /**
     * Pre-tax Store Credit discounts applied to the given orders, from the
     * canonical product_discounts ledger (same query the Sales Summary used).
     */
    private function storeCreditDiscounts(Collection $orderIds): Collection
    {
        return DB::table('product_discounts')
            ->where('discount_type', 'store_credit')
            ->where('target_type', 'order')
            ->where('status', 'applied')
            ->whereIn('target_id', $orderIds)
            ->selectRaw('target_id, COALESCE(SUM(calculated_discount_amount), 0) as disc, COALESCE(SUM(tax_before - tax_after), 0) as tax_delta')
            ->groupBy('target_id')
            ->get()
            ->keyBy('target_id');
    }
}
