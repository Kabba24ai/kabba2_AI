<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BillingRevenueAttributionService — reporting identity for Billing Engine revenue.
 *
 * A Rental Extension child order is OPERATIONALLY its own transaction (child
 * order + extension charge) but FINANCIALLY belongs to the parent rental's
 * product, category, equipment, and store. This service resolves each
 * attributable PAID billing charge to the parent order's PRIMARY RENTAL line:
 * the lowest-id non-deleted order_products row whose product is a Rental,
 * falling back to the lowest-id line only when the parent has no rental
 * lines. Multi-product parents therefore attribute deterministically to
 * their first rental line — an accessory/retail line can never absorb
 * extension revenue. Revenue is exposed grouped by product / category /
 * store, so demand-style reports can add extension revenue under the
 * original rental identity WITHOUT creating another rental, product row,
 * equipment assignment, or transaction count.
 *
 * Semantics (aligned with the Sales Tax Report, the financial source of truth):
 *   - date basis:  billing_charges.paid_at (settlement date)
 *   - settled:     charge status 'paid' AND the child order still holds an
 *                  active Paid payment (gateway voids update that row in
 *                  place, so voided extensions drop out)
 *   - refunds:     netted from the child order's refund rows, ex-tax
 *                  (refund_amount − tax_refunded), floored at 0
 *   - store:       COALESCE(bc.store_id, primary line delivery_store_id)
 *   - category:    the product's primary (lowest-id) category — the same rule
 *                  as SalesReportingService::baseQuery
 *
 * Phase 2B attributes EXTENSION charges only. ATTRIBUTED_TYPES is the single
 * extension point: adding 'damage'/'cleaning'/'fuel'/'delivery' here inherits
 * the same reporting identity in every consuming report with no engine changes.
 */
class BillingRevenueAttributionService
{
    public const ATTRIBUTED_TYPES = ['extension'];

    public function __construct(private SalesReportingService $reporting) {}

    /**
     * Attributed revenue grouped by 'product' | 'category' | 'store' |
     * 'product_store' | 'category_store'. Each row carries the reporting
     * dimensions plus `revenue` (net of child refunds, ex-tax).
     */
    public function groupedRevenue(string $groupBy, array $filters): Collection
    {
        $query = $this->baseQuery($filters);

        if (!$query) {
            return collect();
        }

        $net   = $this->netRevenueExpr();
        $store = 'COALESCE(bc.store_id, pop.delivery_store_id)';

        [$select, $group] = match ($groupBy) {
            'product' => [
                "pop.product_id AS product_id, p.product_name, p.product_type,
                 bpc.id AS category_id, bpc.title AS category_name, SUM({$net}) AS revenue",
                ['pop.product_id', 'p.product_name', 'p.product_type', 'bpc.id', 'bpc.title'],
            ],
            'category' => [
                "bpc.id AS category_id, bpc.title AS category_name, SUM({$net}) AS revenue",
                ['bpc.id', 'bpc.title'],
            ],
            'store' => [
                "{$store} AS store_id, bst.store_name AS store_name, SUM({$net}) AS revenue",
                [DB::raw($store), 'bst.store_name'],
            ],
            'product_store' => [
                "pop.product_id AS product_id, p.product_name,
                 bpc.id AS category_id, bpc.title AS category_name,
                 {$store} AS store_id, bst.store_name AS store_name, SUM({$net}) AS revenue",
                ['pop.product_id', 'p.product_name', 'bpc.id', 'bpc.title', DB::raw($store), 'bst.store_name'],
            ],
            'category_store' => [
                "bpc.id AS category_id, bpc.title AS category_name,
                 {$store} AS store_id, bst.store_name AS store_name, SUM({$net}) AS revenue",
                ['bpc.id', 'bpc.title', DB::raw($store), 'bst.store_name'],
            ],
        };

        return $query->selectRaw($select)->groupBy($group)->get()
            ->filter(fn ($row) => (float) $row->revenue != 0.0)
            ->values();
    }

    /** Total attributed revenue for the period/filters (net of child refunds, ex-tax). */
    public function totalRevenue(array $filters): float
    {
        $query = $this->baseQuery($filters);

        if (!$query) {
            return 0.0;
        }

        return round((float) $query->selectRaw("SUM({$this->netRevenueExpr()}) AS r")->value('r'), 2);
    }

    /**
     * Scope an existing billing_charges query (aliased `billing_charges`) so
     * attributable charge types only match when the PARENT order's primary
     * product line satisfies the active product/category/item-type filters.
     * Non-attributed charge types are left untouched. Used by
     * SalesReportEngineV2 so product/category-filtered views (Product and
     * Category Comparison) stop leaking unrelated extension revenue.
     */
    public static function scopeChargesToParentLine($query, array $filters): void
    {
        $query->where(function ($outer) use ($filters) {
            $outer->whereNotIn('billing_charges.billing_charge_type', self::ATTRIBUTED_TYPES)
                ->orWhereExists(function ($sub) use ($filters) {
                    $sub->selectRaw('1')
                        ->from('order_products as pop')
                        ->join('products as p', 'p.id', '=', 'pop.product_id')
                        ->whereRaw('pop.id = ' . self::primaryParentLineSql('billing_charges'));

                    if (!empty($filters['product'])) {
                        $sub->where('pop.product_id', $filters['product']);
                    }
                    if (!empty($filters['category'])) {
                        $sub->whereRaw(
                            '(SELECT MIN(product_category_id) FROM product_category_children WHERE product_id = pop.product_id) = ?',
                            [$filters['category']]
                        );
                    }
                    if (!empty($filters['item_type']) && $filters['item_type'] !== 'all') {
                        $sub->where('p.product_type', ucfirst($filters['item_type']));
                    }
                    if (!empty($filters['sale_type']) && $filters['sale_type'] !== 'all') {
                        $typeMap = ['rental' => 'Rental', 'retail' => 'Retail'];
                        if (isset($typeMap[$filters['sale_type']])) {
                            $sub->where('p.product_type', $typeMap[$filters['sale_type']]);
                        }
                    }
                });
        });
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function baseQuery(array $filters): ?\Illuminate\Database\Query\Builder
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        if (!$start || !$end) {
            return null;
        }

        // Billing revenue is settled cash — invisible to POD/account-only views
        $paymentStatus = $filters['payment_status'] ?? 'paid';
        if (in_array($paymentStatus, ['pod', 'account'], true)) {
            return null;
        }

        $query = DB::table('billing_charges as bc')
            // Primary RENTAL line: lowest-id non-deleted rental order_products
            // row; falls back to the lowest-id line when no rental line exists
            ->join('order_products as pop', 'pop.id', '=', DB::raw(self::primaryParentLineSql()))
            ->join('products as p', 'p.id', '=', 'pop.product_id')
            ->leftJoinSub(
                DB::table('product_category_children')
                    ->select('product_id', DB::raw('MIN(product_category_id) as primary_category_id'))
                    ->groupBy('product_id'),
                'bpcc', 'bpcc.product_id', '=', 'pop.product_id'
            )
            ->leftJoin('product_categories as bpc', 'bpc.id', '=', 'bpcc.primary_category_id')
            ->leftJoin('stores as bst', 'bst.id', '=', DB::raw('COALESCE(bc.store_id, pop.delivery_store_id)'))
            // Child order refunds, ex-tax, netted off the charge
            ->leftJoinSub(
                DB::table('order_payments')
                    ->selectRaw('order_id, SUM(refund_amount - COALESCE(tax_refunded, 0)) AS refunded_ex_tax')
                    ->whereIn('status', ['Refunded', 'Partial Refund'])
                    ->whereNull('deleted_at')
                    ->groupBy('order_id'),
                'bcr', 'bcr.order_id', '=', 'bc.child_order_id'
            )
            ->whereIn('bc.billing_charge_type', self::ATTRIBUTED_TYPES)
            ->where('bc.status', 'paid')
            ->whereNull('bc.customer_account_id')
            ->whereNull('bc.deleted_at')
            // Settled guard (same semantics as SalesTaxReportEngine): gateway
            // voids flip the child's Paid row to Voided in place
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('order_payments as op_paid')
                    ->whereColumn('op_paid.order_id', 'bc.child_order_id')
                    ->whereNull('op_paid.deleted_at')
                    ->where('op_paid.status', 'Paid');
            })
            ->whereBetween(DB::raw('DATE(bc.paid_at)'), [$start->toDateString(), $end->toDateString()]);

        // Reporting filters — same keys as SalesReportingService::applyFilters
        if (!empty($filters['store']) && $filters['store'] !== 'all_individually' && $filters['store'] !== 'all') {
            $query->whereRaw('COALESCE(bc.store_id, pop.delivery_store_id) = ?', [(int) $filters['store']]);
        }
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('bpcc.primary_category_id', $filters['category']);
        }
        if (!empty($filters['product']) && $filters['product'] !== 'all') {
            $query->where('pop.product_id', $filters['product']);
        }
        if (!empty($filters['item_type']) && $filters['item_type'] !== 'all') {
            $query->where('p.product_type', ucfirst($filters['item_type']));
        }
        if (!empty($filters['sale_type']) && $filters['sale_type'] !== 'all') {
            $typeMap = ['rental' => 'Rental', 'retail' => 'Retail'];
            if (isset($typeMap[$filters['sale_type']])) {
                $query->where('p.product_type', $typeMap[$filters['sale_type']]);
            }
        }

        return $query;
    }

    /** Charge revenue net of child-order refunds (ex-tax), floored at 0. */
    private function netRevenueExpr(): string
    {
        return 'GREATEST(0, bc.amount - COALESCE(bcr.refunded_ex_tax, 0))';
    }

    /**
     * Correlated scalar subquery selecting the parent order's primary RENTAL
     * line id: lowest-id non-deleted rental order_products row, falling back
     * to the lowest-id line when the parent has no rental lines. Accessory or
     * retail lines can never absorb billing revenue while a rental line exists.
     */
    private static function primaryParentLineSql(string $chargeAlias = 'bc'): string
    {
        return "(SELECT op2.id FROM order_products op2
                 JOIN products p2 ON p2.id = op2.product_id
                 WHERE op2.order_id = {$chargeAlias}.parent_order_id AND op2.deleted_at IS NULL
                 ORDER BY (p2.product_type = 'Rental') DESC, op2.id ASC
                 LIMIT 1)";
    }
}
